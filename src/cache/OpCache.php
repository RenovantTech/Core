<?php
namespace renovant\core\cache;
use const renovant\core\{CACHE_DIR, TMP_DIR};
use const renovant\core\trace\T_CACHE;
use renovant\core\sys;
class OpCache implements CacheInterface {
	use \renovant\core\CoreTrait;

	const SQL_INIT = '
		CREATE TABLE IF NOT EXISTS `%s` (
			id VARCHAR NOT NULL,
			tags TEXT NULL default NULL,
			expireAt INTEGER NULL default NULL,
			updateAt INTEGER NOT NULL,
			PRIMARY KEY (id)
		);
	';
	const SQL_SET = 'INSERT OR REPLACE INTO `%s` (id, tags, expireAt, updateAt) VALUES (:id, :tags, :expireAt, :updateAt)';
	const SQL_DELETE = 'DELETE FROM `%s` WHERE id = :id';

	/** Write buffer
	 * @var array */
	static protected $buffer = [];
	/** ID (Cache Identifier)
	 * @var string */
	protected $id;
	/** Memory cache
	 * @var array */
	protected $cache = [];
	/** PDO instance ID
	 * @var string */
	protected $pdo;
	/** PDO table name
	 * @var string */
	protected $table;
	/** Write buffer
	 * @var boolean */
	protected $writeBuffer = false;

	/**
	 * @param string $id cache ID
	 * @param string $pdo PDO instance ID
	 * @param string $table table name
	 * @param bool $writeBuffer write cache at shutdown
	 */
	function __construct($id, $pdo, $table='cache', $writeBuffer=false) {
		$this->id = $id;
		$DIR = CACHE_DIR.'opc-'.$id.'/';
		$this->pdo = $pdo;
		$this->table = $table;
		$this->writeBuffer = (boolean) $writeBuffer;
		sys::trace(LOG_DEBUG, T_CACHE, '[INIT] OpCache directory: '.$DIR, null, $this->_);
		if(!file_exists($DIR))
			mkdir($DIR, 0755, true);
		sys::pdo($pdo)->exec(sprintf(self::SQL_INIT, $table));
	}

	function get($id) {
		if(isset($this->cache[$id])) {
			sys::trace(LOG_DEBUG, T_CACHE, '[MEM] '.$id, null, $this->_);
			return $this->cache[$id];
		} else {
			if(file_exists($file = self::_file($this->id, $id))) include($file);
			if(!isset($data) || (isset($expire) && ($expire!=0 && $expire<time()))) {
				sys::trace(LOG_DEBUG, T_CACHE, '[MISSED] '.$id, null, $this->_);
				return false;
			}
			sys::trace(LOG_DEBUG, T_CACHE, '[HIT] '.$id, null, $this->_);
			return $this->cache[$id] = $data;
		}
	}

	function has($id) {
		if(isset($this->cache[$id])) return true;
		return file_exists(self::_file($this->id, $id));
	}

	function set($id, $value, $expire=null, $tags=null) {
		if($this->writeBuffer) {
			sys::trace(LOG_DEBUG, T_CACHE, '[STORE] '.$id.' (buffered)', null, $this->_);
			self::$buffer[$this->id.'#'.$this->pdo.'#'.$this->table][] = [$id, $value, $expire, $tags];
		} else {
			sys::trace(LOG_DEBUG, T_CACHE, '[STORE] '.$id, null, $this->_);
			$this->_write($this->id, $id, $value, $expire);
			if(is_array($tags)) $tags = implode('|', $tags);
			sys::pdo($this->pdo)->prepare(sprintf(self::SQL_SET, $this->table))
				->execute(['id'=>$id, 'tags'=>$tags, 'expireAt'=>$expire, 'updateAt'=>time()], false);
		}
		$this->cache[$id] = $value;
		return true;

	}

	function delete($id) {
		sys::trace(LOG_DEBUG, T_CACHE, '[DELETE] '.$id, null, $this->_);
		if(isset($this->cache[$id])) {
			$this->cache[$id] = null;
			unset($this->cache[$id]);
		}
		if(file_exists($file = self::_file($this->id, $id))) unlink($file);
		sys::pdo($this->pdo)->prepare(sprintf(self::SQL_DELETE, $this->table))->execute(['id'=>$id], false);
		return true;
	}

	function clean($mode=self::CLEAN_ALL, $tags=null) {
		$this->cache = [];
		unset(self::$buffer[$this->id.'#'.$this->pdo.'#'.$this->table]);
		switch($mode) {
			case self::CLEAN_ALL:
				self::_clean(CACHE_DIR.'opc-'.$this->id);
				sys::pdo($this->pdo)->exec(sprintf('DELETE FROM `%s`',$this->table), false);
				break;
			case self::CLEAN_OLD:
			case self::CLEAN_ALL_TAG:
			case self::CLEAN_ANY_TAG:
			case self::CLEAN_NOT_TAG:
				//@TODO
				break;
		}
		return true;
	}

	static protected function _file($cacheId, $id) {
		return CACHE_DIR.'opc-'.$cacheId.'/'.substr_replace(substr_replace(md5($id), '/', 3,0), '/', 7,0);
	}

	static protected function _write($cacheId, $id, $value, $expire) {
		$data = (is_object($value)) ? 'unserialize(base64_decode(\''.base64_encode(serialize($value)).'\'))' : var_export($value, true);
		$tmp = TMP_DIR.'/opc-'. md5($id);
		$file = self::_file($cacheId, $id);
		if(!file_exists($dir = substr($file, 0, -26)))
			mkdir($dir, 0755, true);
		file_put_contents($tmp, '<?php $expire='.(int)$expire.'; $data='.$data.';', LOCK_EX);
		rename($tmp, $file);
	}

	/**
	 * Recursive directory remove (like UNIX rm -fR /path)
	 * @param string $dir directory
	 */
	static protected function _clean($dir) {
		$files = glob($dir .'/*');
		foreach ($files as $file) {
			if(is_dir($file)) {
				self::_clean($file);
				rmdir($file);
			} else unlink($file);
		}
	}

	/**
	 * Commit write buffer to directory on shutdown
	 */
	static function shutdown() {
		try {
			foreach(self::$buffer as $k=>$buffer) {
				list($cacheId, $pdo, $table) = explode('#', $k);
				sys::trace(LOG_DEBUG, T_CACHE, '[STORE] BUFFER: '.count($buffer).' items on '.$cacheId, null, __METHOD__);
				$pdoSet = sys::pdo($pdo)->prepare(sprintf(self::SQL_SET, $table));
				foreach($buffer as $data) {
					list($id, $value, $expire, $tags) = $data;
					if (is_array($tags)) $tags = implode('|', $tags);
					self::_write($cacheId, $id, $value, $expire);
					@$pdoSet->execute(['id' => $id, 'tags' => $tags, 'expireAt' => $expire, 'updateAt' => time()], false);
				}
			}
		} finally {
			self::$buffer = [];
		}
	}
}
register_shutdown_function(__NAMESPACE__.'\OpCache::shutdown');
