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

	/**
	 * @param string $pdo PDO instance ID
	 * @param string $table table name
	 */
	function __construct($pdo, $table='cache') {
		$this->_oid = $pdo.'.Cache';
		$this->pdo = $pdo;
		$this->table = $table;
		TRACE and $this->trace(LOG_DEBUG, TRACE_CACHE, __FUNCTION__, 'initialize cache storage [Sqlite]');
		Kernel::pdo($pdo)->exec(sprintf(self::SQL_INIT, $table));
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
		$this->trace(LOG_DEBUG, TRACE_CACHE, __FUNCTION__, '[STORE] '.$id);
		if(is_null($this->_pdo_set)) $this->_pdo_set = Kernel::pdo($this->pdo)->prepare(sprintf(self::SQL_SET, $this->table));
		try {
			if(is_array($tags)) $tags = implode('|', $tags);
			$this->_pdo_set->execute(['id'=>$id, 'data'=>serialize($value), 'tags'=>$tags, 'expireAt'=>$expire, 'updateAt'=>time()]);
		} catch(\PDOException $Ex) {
			$this->trace(LOG_ERR, TRACE_ERROR, __FUNCTION__, '[STORE] '.$id.' FAILURE');
			return false;
		}
		$this->cache[$id] = $value;
		return true;
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
}