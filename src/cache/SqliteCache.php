<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\cache;
use const metadigit\core\trace\T_CACHE;
use metadigit\core\sys;
/**
 * Sqlite implementation of CacheInterface
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class SqliteCache implements CacheInterface {
	use \metadigit\core\CoreTrait;

	const SQL_INIT = '
		CREATE TABLE IF NOT EXISTS `%s` (
			id VARCHAR NOT NULL,
			data BLOB NOT NULL,
			tags TEXT NULL default NULL,
			expireAt INTEGER NULL default NULL,
			updateAt INTEGER NOT NULL,
			PRIMARY KEY (id)
		);
	';
	const SQL_GET = 'SELECT data FROM `%s` WHERE id = :id AND (expireAt IS NULL OR expireAt > :t)';
	const SQL_HAS = 'SELECT COUNT(*) FROM `%s` WHERE id = :id';
	const SQL_SET = 'INSERT OR REPLACE INTO `%s` (id, data, tags, expireAt, updateAt) VALUES (:id, :data, :tags, :expireAt, :updateAt)';
	const SQL_DELETE = 'DELETE FROM `%s` WHERE id = :id';

	/** Write buffer
	 * @var array */
	static protected $buffer = [];
	/** Write buffer PDOs
	 * @var array */
	static protected $bufferPDOs = [];

	/** ID (Cache Identifier)
	 * @var string */
	protected $id;
	/** PDOStatement for DELETE
	 * @var \PDOStatement */
	private $_pdo_del;
	/** PDOStatement for SELECT
	 * @var \PDOStatement */
	private $_pdo_get;
	/** PDOStatement for COUNT
	 * @var \PDOStatement */
	private $_pdo_has;
	/** PDOStatement for INSERT/REPLACE
	 * @var \PDOStatement */
	private $_pdo_set;
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
		$this->_ = 'sys.cache.'.strtoupper($id);
		$this->id = $id;
		$this->pdo = $pdo;
		$this->table = $table;
		$this->writeBuffer = (boolean) $writeBuffer;
		// @TODO avoid running INIT on every request, must skip this constructor
		sys::trace(LOG_DEBUG, T_CACHE, '[INIT] Sqlite pdo: '.$pdo.', table: '.$table, null, $this->_);
		sys::pdo($pdo)->exec(sprintf(self::SQL_INIT, $table));
		if($writeBuffer)
			self::$bufferPDOs[$this->id] = [ 'pdo'=>$this->pdo, 'table'=> $this->table ];
	}

	function get($id) {
		if(isset($this->cache[$id])) {
			sys::trace(LOG_DEBUG, T_CACHE, '[MEM] '.$id, null, $this->_);
			return $this->cache[$id];
		} else {
			if(is_null($this->_pdo_get)) $this->_pdo_get = sys::pdo($this->pdo)->prepare(sprintf(self::SQL_GET, $this->table));
			$this->_pdo_get->execute(['id'=>$id, 't'=>time()], false);
			$data = $this->_pdo_get->fetchColumn();
			if($data===false) {
				sys::trace(LOG_DEBUG, T_CACHE, '[MISSED] '.$id, null, $this->_);
				return false;
			}
			sys::trace(LOG_DEBUG, T_CACHE, '[HIT] '.$id, null, $this->_);
			return $this->cache[$id] = unserialize((string)$data);
		}
	}

	function has($id) {
		if(isset($this->cache[$id])) return true;
		if(is_null($this->_pdo_has)) $this->_pdo_has = sys::pdo($this->pdo)->prepare(sprintf(self::SQL_HAS, $this->table));
		$this->_pdo_has->execute(['id'=>$id], false);
		return (boolean)$this->_pdo_has->fetchColumn();
	}

	function set($id, $value, $expire=null, $tags=null) {
		try {
			if($this->writeBuffer) {
				sys::trace(LOG_DEBUG, T_CACHE, '[STORE] '.$id.' (buffered)', null, $this->_);
				self::$buffer[$this->id][] = [$id, serialize($value), $expire, $tags];
			} else {
				sys::trace(LOG_DEBUG, T_CACHE, '[STORE] '.$id, null, $this->_);
				if(is_null($this->_pdo_set)) $this->_pdo_set = sys::pdo($this->pdo)->prepare(sprintf(self::SQL_SET, $this->table));
				if(is_array($tags)) $tags = implode('|', $tags);
				$this->_pdo_set->execute(['id'=>$id, 'data'=>serialize($value), 'tags'=>$tags, 'expireAt'=>$expire, 'updateAt'=>time()], false);
			}
			$this->cache[$id] = $value;
			return true;
		} catch(\PDOException $Ex) {
			sys::trace(LOG_ERR, T_CACHE, '[STORE] '.$id.' FAILURE', null, $this->_);
			return false;
		}
	}

	function delete($id) {
		sys::trace(LOG_DEBUG, T_CACHE, '[DELETE] '.$id, null, $this->_);
		if(isset($this->cache[$id])) unset($this->cache[$id]);
		if(is_null($this->_pdo_del)) $this->_pdo_del = sys::pdo($this->pdo)->prepare(sprintf(self::SQL_DELETE, $this->table));
		$this->_pdo_del->execute(['id'=>$id], false);
		return true;
	}

	function clean($mode=self::CLEAN_ALL, $tags=null) {
		$this->cache = [];
		switch($mode) {
			case self::CLEAN_ALL:
				sys::pdo($this->pdo)->exec(sprintf('DELETE FROM `%s`',$this->table), false);
				break;
			case self::CLEAN_OLD:
				sys::pdo($this->pdo)->exec(sprintf('DELETE FROM `%s` WHERE expireAt <= %s',$this->table, time()), false);
				break;
			case self::CLEAN_ALL_TAG:
				//@TODO
				break;
			case self::CLEAN_ANY_TAG:
				//@TODO
				break;
			case self::CLEAN_NOT_TAG:
				//@TODO
				break;
		}
		file_put_contents(\metadigit\core\TMP_DIR.$this->pdo.'.vacuum','');
		return true;
	}

	/**
	 * Commit write buffer to SqLite on shutdown
	 */
	static function shutdown() {
		foreach(self::$buffer as $k=>$buffer) {
			if(!isset(self::$bufferPDOs[$k])) continue;
			sys::trace(LOG_DEBUG, T_CACHE, '[STORE] BUFFER: '.count($buffer).' items on '.$k, null, __METHOD__);
			$pdoSet = sys::pdo(self::$bufferPDOs[$k]['pdo'])->prepare(sprintf(self::SQL_SET, self::$bufferPDOs[$k]['table']));
			foreach($buffer as $data) {
				list($id, $value, $expire, $tags) = $data;
				if(is_array($tags)) $tags = implode('|', $tags);
				@$pdoSet->execute(['id'=>$id, 'data'=>$value, 'tags'=>$tags, 'expireAt'=>$expire, 'updateAt'=>time()], false);
			}
		}
	}
}
register_shutdown_function(__NAMESPACE__.'\SqliteCache::shutdown');
