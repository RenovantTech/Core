<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\cache;
use const renovant\core\CACHE_DIR;
use const renovant\core\trace\T_CACHE;
use renovant\core\sys;
/**
 * Sqlite implementation of CacheInterface
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
class SqliteCache implements CacheInterface {
	use \renovant\core\CoreTrait;

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
	/** ID (Cache Identifier)
	 * @var string */
	protected $id;
	/** PDOStatement for DELETE
	 * @var \SQLite3 */
	private $db;
	/** PDOStatement for DELETE
	 * @var \PDOStatement */
	private $_sql_del;
	/** PDOStatement for SELECT
	 * @var \PDOStatement */
	private $_sql_get;
	/** PDOStatement for COUNT
	 * @var \PDOStatement */
	private $_sql_has;
	/** PDOStatement for INSERT/REPLACE
	 * @var \PDOStatement */
	private $_sql_set;
	/** Memory cache
	 * @var array */
	protected $cache = [];
	/** PDO table name
	 * @var string */
	protected $table;
	/** Write buffer
	 * @var boolean */
	protected $writeBuffer = false;

	/**
	 * @param string $id cache ID
	 * @param string $table table name
	 * @param bool $writeBuffer write cache at shutdown
	 */
	function __construct($id, $table='cache', $writeBuffer=false) {
		$this->id = $id;
		$this->table = $table;
		$this->writeBuffer = (boolean) $writeBuffer;
		sys::trace(LOG_DEBUG, T_CACHE, '[INIT] Sqlite: '.$id.', table: '.$table, null, $this->_);
		$this->db = new \SQLite3(CACHE_DIR.$id.'.sqlite', SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
		$this->db->exec(sprintf(self::SQL_INIT, $table));
	}

	function __sleep() {
		return ['_', 'id', 'table', 'writeBuffer'];
	}

	function get($id) {
		if(isset($this->cache[$id])) {
			sys::trace(LOG_DEBUG, T_CACHE, '[MEM] '.$id, null, $this->_);
			return $this->cache[$id];
		} else {
			if(!$this->db) $this->db = new \SQLite3(CACHE_DIR.$this->id.'.sqlite', SQLITE3_OPEN_READONLY);
			if(is_null($this->_sql_get)) $this->_sql_get = $this->db->prepare(sprintf(self::SQL_GET, $this->table));
			$this->_sql_get->bindValue('id', $id);
			$this->_sql_get->bindValue('t', time());
			$res = $this->_sql_get->execute();
			$data = false;
			if($res)
				$data = $res->fetchArray(SQLITE3_NUM);
			if($res===false || $data === false) {
				sys::trace(LOG_DEBUG, T_CACHE, '[MISSED] '.$id, null, $this->_);
				return false;
			}
			sys::trace(LOG_DEBUG, T_CACHE, '[HIT] '.$id, null, $this->_);
			return $this->cache[$id] = unserialize((string)$data[0]);
		}
	}

	function has($id) {
		if(isset($this->cache[$id])) return true;
		if(!$this->db) $this->db = new \SQLite3(CACHE_DIR.$this->id.'.sqlite', SQLITE3_OPEN_READONLY);
		if(is_null($this->_sql_has)) $this->_sql_has = $this->db->prepare(sprintf(self::SQL_HAS, $this->table));
		$this->_sql_has->bindValue('id', $id);
		$data = $this->_sql_has->execute()->fetchArray(SQLITE3_NUM);
		return (boolean)$data[0];
	}

	function set($id, $value, $expire=null, $tags=null) {
		static $db;
		try {
			if($this->writeBuffer) {
				sys::trace(LOG_DEBUG, T_CACHE, '[STORE] '.$id.' (buffered)', null, $this->_);
				self::$buffer[$this->id.'#'.$this->table][] = [$id, serialize($value), $expire, $tags];
			} else {
				sys::trace(LOG_DEBUG, T_CACHE, '[STORE] '.$id, null, $this->_);
				if(!$db) $db = new \SQLite3(CACHE_DIR.$this->id.'.sqlite', SQLITE3_OPEN_READWRITE);
				if(is_null($this->_sql_set)) $this->_sql_set = $db->prepare(sprintf(self::SQL_SET, $this->table));
				if(is_array($tags)) $tags = implode('|', $tags);
				$this->_sql_set->bindValue('id', $id);
				$this->_sql_set->bindValue('data', serialize($value), SQLITE3_BLOB);
				$this->_sql_set->bindValue('tags', $tags);
				$this->_sql_set->bindValue('expireAt', $expire);
				$this->_sql_set->bindValue('updateAt', time());
				$this->_sql_set->execute();
				unset($db);
			}
			$this->cache[$id] = $value;
			return true;
		} catch(\PDOException $Ex) {
			sys::trace(LOG_ERR, T_CACHE, '[STORE] '.$id.' FAILURE', null, $this->_);
			return false;
		}
	}

	function delete($id) {
		static $db;
		sys::trace(LOG_DEBUG, T_CACHE, '[DELETE] '.$id, null, $this->_);
		if(isset($this->cache[$id])) {
			$this->cache[$id] = null;
			unset($this->cache[$id]);
		}
		if(!$db) $db = new \SQLite3(CACHE_DIR.$this->id.'.sqlite', SQLITE3_OPEN_READWRITE);
		if(is_null($this->_sql_del)) $this->_sql_del = $db->prepare(sprintf(self::SQL_DELETE, $this->table));
		$this->_sql_del->bindValue('id', $id);
		$this->_sql_del->execute();
		unset($db);
		return true;
	}

	function clean($mode=self::CLEAN_ALL, $tags=null) {
		static $db;
		if(!$db) $db = new \SQLite3(CACHE_DIR.$this->id.'.sqlite', SQLITE3_OPEN_READWRITE);
		$this->cache = [];
		switch($mode) {
			case self::CLEAN_ALL:
				$db->exec(sprintf('DELETE FROM `%s`',$this->table));
				break;
			case self::CLEAN_OLD:
				$db->exec(sprintf('DELETE FROM `%s` WHERE expireAt <= %s',$this->table, time()));
				break;
			case self::CLEAN_ALL_TAG:
			case self::CLEAN_ANY_TAG:
			case self::CLEAN_NOT_TAG:
				//@TODO
				break;
		}
		unset($db);
		return true;
	}

	/**
	 * Commit write buffer to SqLite on shutdown
	 */
	static function shutdown() {
		try {
			foreach (self::$buffer as $k => $buffer) {
				sys::trace(LOG_DEBUG, T_CACHE, '[STORE] BUFFER: ' . count($buffer) . ' items on ' . $k, null, __METHOD__);
				list($id, $table) = explode('#', $k);
				$db = new \SQLite3(CACHE_DIR.$id.'.sqlite', SQLITE3_OPEN_READWRITE);
				$sqlSet = $db->prepare(sprintf(self::SQL_SET, $table));
				foreach ($buffer as $data) {
					list($id, $value, $expire, $tags) = $data;
					if (is_array($tags)) $tags = implode('|', $tags);
					$sqlSet->bindValue('id', $id);
					$sqlSet->bindValue('data', $value, SQLITE3_BLOB);
					$sqlSet->bindValue('tags', $tags);
					$sqlSet->bindValue('expireAt', $expire);
					$sqlSet->bindValue('updateAt', time());
					$sqlSet->execute();
				}
				unset($db);
			}
		} finally {
			self::$buffer = [];
		}
	}
}
register_shutdown_function(__NAMESPACE__.'\SqliteCache::shutdown');
