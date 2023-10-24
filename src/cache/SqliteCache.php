<?php
namespace renovant\core\cache;
use const renovant\core\CACHE_DIR;
use const renovant\core\trace\{T_CACHE,T_ERROR};
use renovant\core\sys;
class SqliteCache implements CacheInterface {
	use \renovant\core\CoreTrait;

	const FILE_EXT = '.sqlite';
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
	const SQL_GET = 'SELECT data FROM `%s` WHERE id = :id AND (expireAt = 0 OR expireAt > :t)';
	const SQL_HAS = 'SELECT COUNT(*) FROM `%s` WHERE id = :id';
	const SQL_SET = 'INSERT OR REPLACE INTO `%s` (id, data, tags, expireAt, updateAt) VALUES (:id, :data, :tags, :expireAt, :updateAt)';
	const SQL_DELETE = 'DELETE FROM `%s` WHERE id = :id';

	/** Write buffer */
	static protected array $buffer = [];
	/** File name inside CACHE_DIR */
	protected string $filename;
	/** Memory cache */
	protected array $cache = [];
	/** SQLite3 resource (READ only) */
	protected \SQLite3 $db;
	/** SQLite3 resource (READ/WRITE) */
	protected \SQLite3 $dbRW;
	/** SQLite Statement for DELETE */
	protected ?\SQLite3Stmt $SqlDEL = null;
	/** SQLite Statement for SELECT */
	protected ?\SQLite3Stmt $SqlGET = null;
	/** SQLite Statement for COUNT */
	protected ?\SQLite3Stmt $SqlHAS = null;
	/** SQLite Statement for INSERT/REPLACE */
	protected ?\SQLite3Stmt $SqlSET = null;
	/** PDO table name */
	protected string $table;
	/** Write buffer */
	protected bool $writeBuffer = false;

	/**
	 * @param string $filename SqLite file name
	 * @param string $table table name
	 * @param bool $writeBuffer write cache at shutdown
	 */
	function __construct(string $filename, string $table='cache', bool $writeBuffer=false) {
		$this->filename = $filename;
		$this->table = $table;
		$this->writeBuffer = (boolean) $writeBuffer;
		$this->__init('INIT');
	}

	function __sleep() {
		return ['_', 'filename', 'table', 'writeBuffer'];
	}

	function __wakeup() {
		$this->__init();
	}

	protected function __init(string $mode='R') {
		$file = CACHE_DIR.$this->filename.self::FILE_EXT;
		sys::trace(LOG_DEBUG, T_CACHE, '[INIT] SQLite3 ('.$mode.'): '.$file.', table: '.$this->table, null, $this->_);
		try {
			switch ($mode) {
				case 'INIT':
					$this->dbRW = new \SQLite3($file, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
					$this->dbRW->busyTimeout(10);
					$this->dbRW->exec(sprintf(self::SQL_INIT, $this->table));
					if($this->writeBuffer) {
						$this->dbRW->close();
						unset($this->dbRW);
					}
					break;
				case 'RW':
					$this->dbRW = new \SQLite3($file, SQLITE3_OPEN_READWRITE);
					$this->dbRW->busyTimeout(10);
					$this->SqlSET = $this->dbRW->prepare(sprintf(self::SQL_SET, $this->table));
					$this->SqlDEL = $this->dbRW->prepare(sprintf(self::SQL_DELETE, $this->table));
					if(!$this->SqlSET || !$this->SqlDEL)
						sys::trace(LOG_ERR, T_ERROR, '[INIT] '.$this->filename.' (RW) FAILURE', null, $this->_);
					break;
				case 'R':
					$this->db = new \SQLite3($file, SQLITE3_OPEN_READONLY);
					$this->db->busyTimeout(50);
					$this->SqlGET = $this->db->prepare(sprintf(self::SQL_GET, $this->table));
					$this->SqlHAS = $this->db->prepare(sprintf(self::SQL_HAS, $this->table));
					if(!$this->SqlGET || !$this->SqlHAS)
						sys::trace(LOG_ERR, T_ERROR, '[INIT] '.$this->filename.' (R) FAILURE', null, $this->_);
					break;
			}
		}  catch(\Exception $Ex) {
			sys::trace(LOG_ERR, T_ERROR, '[INIT] FAILURE', null, $this->_);
		}
	}

	function get(string $id) {
		if(isset($this->cache[$id])) {
			sys::trace(LOG_DEBUG, T_CACHE, '[MEM] ' . $id, null, $this->_);
			return $this->cache[$id];
		}
		try {
			if(is_null($this->SqlGET)) $this->__init('R');
			$this->SqlGET->bindValue('id', $id);
			$this->SqlGET->bindValue('t', time());
			if($res = $this->SqlGET->execute()) { /** @var \SQLite3Result $res */
				$data = $res->fetchArray(SQLITE3_NUM);
				$res->finalize();
				if($data) {
					sys::trace(LOG_DEBUG, T_CACHE, '[HIT] '.$id, null, $this->_);
					return $this->cache[$id] = unserialize((string)$data[0]);
				}
			}
			sys::trace(LOG_DEBUG, T_CACHE, '[MISSED] '.$id, null, $this->_);
			return false;
		} catch(\Exception $Ex) {
			sys::trace(LOG_ERR, T_ERROR, '[GET] '.$id.' FAILURE', null, $this->_);
			return false;
		}
	}

	function has(string $id): bool {
		if(isset($this->cache[$id]))
			return true;
		try {
			if(is_null($this->SqlHAS)) $this->__init('R');
			$this->SqlHAS->bindValue('id', $id);
			if($res = $this->SqlHAS->execute()) { /** @var \SQLite3Result $res */
				$data = $res->fetchArray(SQLITE3_NUM);
				$res->finalize();
				if($data)
					return (boolean)$data[0];
			}
			return false;
		} catch(\Exception $Ex) {
			sys::trace(LOG_ERR, T_ERROR, '[HAS] '.$id.' FAILURE', null, $this->_);
			return false;
		}
	}

	function set(string $id, mixed $value, int $expire=0, mixed $tags=null): bool {
		try {
			if($this->writeBuffer) {
				sys::trace(LOG_DEBUG, T_CACHE, '[STORE] '.$id.' (buffered)', null, $this->_);
				self::$buffer[$this->filename.':'.$this->table][$id] = [serialize($value), $expire, $tags];
			} else {
				if(is_null($this->SqlSET)) $this->__init('RW');
				sys::trace(LOG_DEBUG, T_CACHE, '[STORE] '.$id, null, $this->_);
				if(is_array($tags)) $tags = implode('|', $tags);
				$this->SqlSET->bindValue('id', $id);
				$this->SqlSET->bindValue('data', serialize($value), SQLITE3_BLOB);
				$this->SqlSET->bindValue('tags', $tags);
				$this->SqlSET->bindValue('expireAt', $expire);
				$this->SqlSET->bindValue('updateAt', time());
				if(false === $this->SqlSET->execute())
					throw new \Exception();
			}
			$this->cache[$id] = $value;
			return true;
		} catch(\Exception $Ex) {
			sys::trace(LOG_ERR, T_ERROR, '[STORE] '.$id.' FAILURE', null, $this->_);
			return false;
		}
	}

	function delete(string $id): bool {
		if(isset($this->cache[$id])) {
			$this->cache[$id] = null;
			unset($this->cache[$id]);
		}
		try {
			if(is_null($this->SqlDEL)) $this->__init('RW');
			sys::trace(LOG_DEBUG, T_CACHE, '[DELETE] '.$id, null, $this->_);
			$this->SqlDEL->bindValue('id', $id);
			if(false === $this->SqlDEL->execute())
				throw new \Exception();
			return true;
		} catch(\Exception $Ex) {
			sys::trace(LOG_ERR, T_ERROR, '[DELETE] '.$id.' FAILURE', null, $this->_);
			return false;
		}
	}

	function clean(int $mode=self::CLEAN_ALL, $tags=null): bool {
		if(is_null($this->SqlDEL)) $this->__init('RW');
		sys::trace(LOG_DEBUG, T_CACHE, '[CLEAN]', null, $this->_);
		$this->cache = [];
		switch($mode) {
			case self::CLEAN_ALL:
				$this->dbRW->exec(sprintf('DELETE FROM `%s`',$this->table));
				break;
			case self::CLEAN_OLD:
				$this->dbRW->exec(sprintf('DELETE FROM `%s` WHERE expireAt <= %s',$this->table, time()));
				break;
			case self::CLEAN_ALL_TAG:
			case self::CLEAN_ANY_TAG:
			case self::CLEAN_NOT_TAG:
				//@TODO
				break;
		}
		return true;
	}

	/**
	 * Commit write buffer to SqLite on shutdown
	 */
	static function shutdown() {
		try {
			foreach (self::$buffer as $k => $buffer) {
				sys::trace(LOG_DEBUG, T_CACHE, '[STORE] BUFFER: '.count($buffer).' items on '.$k, null, __METHOD__);
				list($filename, $table) = explode(':', $k);
				$db = new \SQLite3(CACHE_DIR.$filename.self::FILE_EXT, SQLITE3_OPEN_READWRITE);
				$sqlSet = $db->prepare(sprintf(self::SQL_SET, $table));
				foreach ($buffer as $id => $data) {
					list($value, $expire, $tags) = $data;
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
