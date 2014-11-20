<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\cache;
use metadigit\core\Kernel;
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
	static protected $_buffer = [];

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
	 * @param string $pdo PDO instance ID
	 * @param string $table table name
	 * @param bool $writeBuffer write cache at shutdown
	 */
	function __construct($pdo, $table='cache', $writeBuffer=false) {
		$this->_oid = $pdo.'#'.$table;
		$this->pdo = $pdo;
		$this->table = $table;
		$this->writeBuffer = (boolean) $writeBuffer;
		TRACE and $this->trace(LOG_DEBUG, TRACE_CACHE, __FUNCTION__, '[INIT] Sqlite pdo: '.$pdo.', table: '.$table);
		Kernel::pdo($pdo)->exec(sprintf(self::SQL_INIT, $table));
		if($writeBuffer) {
			$this->writeBuffer = true;
			self::$_buffer[$this->pdo.'#'.$this->table]['pdoSt'] = $this->_pdo_set = Kernel::pdo($this->pdo)->prepare(sprintf(self::SQL_SET, $this->table));
		}
	}

	function get($id) {
		if(isset($this->cache[$id])) {
			$this->trace(LOG_DEBUG, TRACE_CACHE,  __FUNCTION__, '[MEM] '.$id);
			return $this->cache[$id];
		} else {
			if(is_null($this->_pdo_get)) $this->_pdo_get = Kernel::pdo($this->pdo)->prepare(sprintf(self::SQL_GET, $this->table));
			$this->_pdo_get->execute(['id'=>$id, 't'=>time()]);
			$data = $this->_pdo_get->fetchColumn();
			if($data===false) {
				$this->trace(LOG_DEBUG, TRACE_CACHE, __FUNCTION__, '[MISSED] '.$id);
				return false;
			}
			$this->trace(LOG_DEBUG, TRACE_CACHE, __FUNCTION__, '[HIT] '.$id);
			return $this->cache[$id] = unserialize((string)$data);
		}
	}

	function has($id) {
		if(is_null($this->_pdo_has)) $this->_pdo_has = Kernel::pdo($this->pdo)->prepare(sprintf(self::SQL_HAS, $this->table));
		$this->_pdo_has->execute(['id'=>$id]);
		return (boolean)$this->_pdo_has->fetchColumn();
	}

	function mget(array $ids) {
		// @TODO mget()
	}

	function set($id, $value, $expire=null, $tags=null) {
		try {
			if($this->writeBuffer) {
				$this->trace(LOG_DEBUG, TRACE_CACHE, __FUNCTION__, '[STORE] '.$id.' (buffered)');
				self::$_buffer[$this->pdo.'#'.$this->table]['store'][] = [$id, $value, $expire, $tags];
			} else {
				$this->trace(LOG_DEBUG, TRACE_CACHE, __FUNCTION__, '[STORE] '.$id);
				if(is_null($this->_pdo_set)) $this->_pdo_set = Kernel::pdo($this->pdo)->prepare(sprintf(self::SQL_SET, $this->table));
				if(is_array($tags)) $tags = implode('|', $tags);
				$this->_pdo_set->execute(['id'=>$id, 'data'=>serialize($value), 'tags'=>$tags, 'expireAt'=>$expire, 'updateAt'=>time()]);
			}
			$this->cache[$id] = $value;
			return true;
		} catch(\PDOException $Ex) {
			$this->trace(LOG_ERR, TRACE_ERROR, __FUNCTION__, '[STORE] '.$id.' FAILURE');
			return false;
		}
	}

	function delete($id) {
		$this->trace(LOG_DEBUG, TRACE_CACHE, __FUNCTION__, '[DELETE] '.$id);
		if(isset($this->cache[$id])) unset($this->cache[$id]);
		if(is_null($this->_pdo_del)) $this->_pdo_del = Kernel::pdo($this->pdo)->prepare(sprintf(self::SQL_DELETE, $this->table));
		$this->_pdo_del->execute(['id'=>$id]);
		return true;
	}

	function clean($mode=self::CLEAN_ALL, $tags=null) {
		$this->cache = [];
		switch($mode) {
			case self::CLEAN_ALL:
				Kernel::pdo($this->pdo)->exec(sprintf('DELETE FROM `%s`',$this->table));
				break;
			case self::CLEAN_OLD:
				Kernel::pdo($this->pdo)->exec(sprintf('DELETE FROM `%s` WHERE expireAt <= %s',$this->table, time()));
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
		foreach(self::$_buffer as $k=>$buffer) {
			if(!isset($buffer['pdoSt']) || !isset($buffer['store'])) continue;
			Kernel::trace(LOG_DEBUG, TRACE_CACHE, __METHOD__, '[STORE] BUFFER: '.count($buffer['store']).' items on '.$k);
			foreach($buffer['store'] as $data) {
				list($id, $value, $expire, $tags) = $data;
				if(is_array($tags)) $tags = implode('|', $tags);
				@$buffer['pdoSt']->execute(['id'=>$id, 'data'=>serialize($value), 'tags'=>$tags, 'expireAt'=>$expire, 'updateAt'=>time()]);
			}
		}
	}
}
