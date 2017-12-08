<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\db\orm;
use const metadigit\core\ACL_ORM;
use const metadigit\core\trace\T_INFO;
use metadigit\core\sys,
	metadigit\core\db\orm\util\DataMapper,
	metadigit\core\db\orm\util\QueryRunner,
	metadigit\core\util\validator\Validator;
/**
 * ORM Repository
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Repository {
	use \metadigit\core\CoreTrait;
	const ACL_SKIP = true;

	/** FETCH MODE as objects */
	const FETCH_OBJ		= 1;
	/** FETCH MODE as array (with data type mapping) */
	const FETCH_ARRAY	= 2;
	/** FETCH MODE as array for JSON output (with data type mapping) */
	const FETCH_JSON	= 3;
	/** Entity class
	 * @var string */
	protected $class;
	/** PDO instance ID
	 * @var string */
	protected $pdo = 'master';

	protected $_onInit;
	protected $_onSave;
	protected $_onDelete;

	/**
	 * @param string $class Entity class
	 * @param string $pdo PDO instance ID, default to "master"
	 */
	function __construct($class, $pdo='master') {
		$this->class = $class;
		$this->pdo = $pdo;
		class_exists($class);
		$this->_onInit = new \ReflectionMethod($class, 'onInit');
		$this->_onInit->setAccessible(true);
		$this->_onSave = new \ReflectionMethod($class, 'onSave');
		$this->_onSave->setAccessible(true);
		$this->_onDelete = new \ReflectionMethod($class, 'onDelete');
		$this->_onDelete->setAccessible(true);
	}

	function __sleep() {
		return ['_oid', 'class', 'pdo'];
	}

	function __wakeup() {
		class_exists($this->class);
		$this->_onInit = new \ReflectionMethod($this->class, 'onInit');
		$this->_onInit->setAccessible(true);
		$this->_onSave = new \ReflectionMethod($this->class, 'onSave');
		$this->_onSave->setAccessible(true);
		$this->_onDelete = new \ReflectionMethod($this->class, 'onDelete');
		$this->_onDelete->setAccessible(true);
	}

	/**
	 * Convert entities objects to array
	 * @param object|array $entities one or more Entities
	 * @param string|null $subset
	 * @return array
	 */
	function toArray($entities, $subset=null) {
		$data = [];
		if(is_array($entities)) {
			foreach($entities as $Entity) {
				$data[] = DataMapper::object2array($Entity, $subset);
			}
		} elseif(is_object($entities))
			$data = DataMapper::object2array($entities, $subset);
		else trigger_error('Invalid data');
		return $data;
	}

	/**
	 * Count entities by a set of Criteria
	 * @param string|null $criteriaExp CRITERIA expression
	 * @return integer
	 */
	function count($criteriaExp=null) {
		return $this->execCount($criteriaExp);
	}

	/**
	 * Create new Entity instance
	 * @param array $data Entity data
	 * @return object Entity
	 */
	function create(array $data=[]) {
		return new $this->class($data);
	}

	/**
	 * Delete Entity from DB
	 * @param mixed $EntityOrKey object or its primary keys
	 * @param int $fetchMode fetch mode (FETCH_OBJ, FETCH_ARRAY, FETCH_JSON), FALSE to skip fetch before delete
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return mixed $Entity object or array if $fetchMode, TRUE if not $fetchMode, FALSE on failure
	 * @throws Exception
	 */
	function delete($EntityOrKey, $fetchMode=self::FETCH_OBJ, $fetchSubset=null) {
		if(is_object($EntityOrKey)) {
			$Entity = $EntityOrKey;
		} else {
			$criteriaExp = Metadata::get($this->class)->pkCriteria($EntityOrKey);
			$Entity = $this->execFetchOne(0, null, $criteriaExp);
		}
		return $this->execDeleteOne($Entity, $fetchMode, $fetchSubset);
	}

	/**
	 * Fetch an Entity by a set of Criteria and ORDER BY
	 * @param integer $limit LIMIT
	 * @param string|null $orderExp ORDER BY expression
	 * @param string|null $criteriaExp CRITERIA expression
	 * @return integer n° of deleted entities
	 * @throws Exception
	 */
	function deleteAll($limit, $orderExp=null, $criteriaExp=null) {
		return $this->execDeleteAll($limit, $orderExp, $criteriaExp);
	}

	/**
	 * Fetch an Entity by its primary key
	 * @param mixed $id Entity primary key (single or array)
	 * @param int $fetchMode fetch mode: FETCH_OBJ, FETCH_ARRAY, FETCH_JSON
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return object|false Entity, false if not found
	 * @throws Exception
	 */
	function fetch($id, $fetchMode=self::FETCH_OBJ, $fetchSubset=null) {
		$criteriaExp = Metadata::get($this->class)->pkCriteria($id);
		return $this->execFetchOne(0, null, $criteriaExp, $fetchMode, $fetchSubset);
	}

	/**
	 * Fetch an Entity by a set of Criteria and ORDER BY
	 * @param int|null $offset (starting from 1)
	 * @param string|null $orderExp ORDER BY expression
	 * @param string|null $criteriaExp CRITERIA expression
	 * @param int $fetchMode fetch mode: FETCH_OBJ, FETCH_ARRAY, FETCH_JSON
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return object|array|null Entity, NULL if not found
	 * @throws Exception
	 */
	function fetchOne($offset=null, $orderExp=null, $criteriaExp=null, $fetchMode=self::FETCH_OBJ, $fetchSubset=null) {
		if($offset) $offset--;
		return $this->execFetchOne($offset, $orderExp, $criteriaExp, $fetchMode, $fetchSubset);
	}

	/**
	 * Fetch an array of entities by a set of Criteria and ORDER BY
	 * @param int|null $page page n°
	 * @param int|null $pageSize page size, NULL to fetch all
	 * @param string|null $orderExp ORDER BY expression
	 * @param string|null $criteriaExp CRITERIA expression
	 * @param int $fetchMode fetch mode: FETCH_OBJ, FETCH_ARRAY, FETCH_JSON
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return array
	 * @throws Exception
	 */
	function fetchAll($page=null, $pageSize=null, $orderExp=null, $criteriaExp=null, $fetchMode=self::FETCH_OBJ, $fetchSubset=null) {
		$offset = ($page && $pageSize) ? $pageSize * $page - $pageSize : null;
		return $this->execFetchAll($offset, $pageSize, $orderExp, $criteriaExp, $fetchMode, $fetchSubset);
	}

	/**
	 * Insert Entity into DB
	 * @param mixed $id primary key(s)
	 * @param array|object $data new Entity data, or Entity object
	 * @param bool|string $validate TRUE to validate all, a named @orm-validate-subset, or FALSE to skip validation
	 * @param int $fetchMode fetch mode (FETCH_OBJ, FETCH_ARRAY, FETCH_JSON), FALSE to skip fetch after insert
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return mixed $Entity object or array if $fetchMode, TRUE if not $fetchMode, FALSE on failure
	 * @throws Exception
	 */
	function insert($id, $data=[], $validate=true, $fetchMode=self::FETCH_OBJ, $fetchSubset=null) {
		return $this->execInsertOne($id, $data, $validate, $fetchMode, $fetchSubset);
	}

	/**
	 * Update Entity into DB
	 * @param mixed $id primary key(s)
	 * @param array|object $data new Entity data, or Entity object
	 * @param bool|string $validate TRUE to validate all, a named @orm-validate-subset, or FALSE to skip validation
	 * @param int $fetchMode fetch mode (FETCH_OBJ, FETCH_ARRAY, FETCH_JSON), FALSE to skip fetch after insert
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return mixed $Entity object or array if $fetchMode, TRUE if not $fetchMode, FALSE on failure
	 * @throws Exception
	 */
	function update($id, $data=[], $validate=true, $fetchMode=self::FETCH_OBJ, $fetchSubset=null) {
		return $this->execUpdateOne($id, $data, $validate, $fetchMode, $fetchSubset);
	}

	/**
	 * Validate Entity.
	 * Empty implementation, can be overridden by subclasses
	 * @param $Entity
	 * @param string|null $validateMode
	 * @return array map of properties & error codes, empty if VALID
	 */
	function validate($Entity, $validateMode) {
		return [];
	}

	/**
	 * Count entities using a Query.
	 * @param string|null $criteriaExp CRITERIA expression
	 * @return integer
	 * @throws Exception
	 */
	protected function execCount($criteriaExp=null) {
		$OrmEvent = (new OrmEvent($this))->criteriaExp($criteriaExp);
		try {
			sys::event(OrmEvent::EVENT_PRE_COUNT, null, null, $OrmEvent);
			ACL_ORM and sys::acl()->onOrm($this->_oid, 'COUNT', SESSION_UID);
			return QueryRunner::count($this->pdo, $this->class, $OrmEvent->getCriteriaExp());
		} catch(\PDOException $Ex){
			throw new Exception(200, [$this->_oid, $Ex->getCode(), $Ex->getMessage()]);
		}
	}

	/**
	 * Delete Entity using a Query.
	 * @param mixed $Entity object or its primary keys
	 * @param int $fetchMode fetch mode (FETCH_OBJ, FETCH_ARRAY, FETCH_JSON), FALSE to skip fetch after insert
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return mixed $Entity object or array if $fetchMode, TRUE if not $fetchMode, FALSE on failure
	 * @throws Exception
	 */
	protected function execDeleteOne($Entity, $fetchMode=self::FETCH_OBJ, $fetchSubset=null) {
		$OrmEvent = (new OrmEvent($this))->setEntity($Entity);
		try {
			sys::event(OrmEvent::EVENT_PRE_DELETE, null, null, $OrmEvent);
			ACL_ORM and sys::acl()->onOrm($this->_oid, 'DELETE', SESSION_UID);
			if(QueryRunner::deleteOne($this->pdo, $this->class, $Entity, $OrmEvent->getCriteriaExp())) {
				$this->_onDelete->invoke($Entity);
				sys::event(OrmEvent::EVENT_POST_DELETE, null, null, $OrmEvent);
				switch($fetchMode) {
					case self::FETCH_OBJ: return $Entity; break;
					case self::FETCH_ARRAY: return DataMapper::object2array($Entity, $fetchSubset); break;
					case self::FETCH_JSON: return DataMapper::object2json($Entity, $fetchSubset); break;
					case false: return true;
				}
			}
			return false;
		} catch(\PDOException $Ex) {
			throw new Exception(400, [$this->_oid, $Ex->getCode(), $Ex->getMessage()]);
		}
	}

	/**
	 * Delete entities using a Query.
	 * @param int $limit LIMIT
	 * @param string|null $orderExp ORDER BY expression
	 * @param string|null $criteriaExp CRITERIA expression
	 * @return integer n° of deleted entities
	 * @throws Exception
	 */
	protected function execDeleteAll($limit, $orderExp=null, $criteriaExp=null) {
		$OrmEvent = (new OrmEvent($this))->criteriaExp($criteriaExp);
		try {
			sys::event(OrmEvent::EVENT_PRE_DELETE_ALL, null, null, $OrmEvent);
			ACL_ORM and sys::acl()->onOrm($this->_oid, 'DELETE', SESSION_UID);
			$n = QueryRunner::deleteAll($this->pdo, $this->class, $limit, $orderExp, $OrmEvent->getCriteriaExp());
			sys::event(OrmEvent::EVENT_POST_DELETE_ALL, null, null, $OrmEvent);
			return $n;
		} catch(\PDOException $Ex) {
			throw new Exception(400, [$this->_oid, $Ex->getCode(), $Ex->getMessage()]);
		}
	}

	/**
	 * Fetch an Entity using a Query.
	 * @param int|null $offset OFFSET
	 * @param string|null $orderExp ORDER BY expression
	 * @param string|null $criteriaExp CRITERIA expression
	 * @param int $fetchMode fetch mode: FETCH_OBJ, FETCH_ARRAY, FETCH_JSON
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return object Entity
	 * @throws Exception
	 */
	protected function execFetchOne($offset=null, $orderExp=null, $criteriaExp=null, $fetchMode=self::FETCH_OBJ, $fetchSubset=null) {
		$OrmEvent = (new OrmEvent($this))->criteriaExp($criteriaExp);
		try {
			sys::event(OrmEvent::EVENT_PRE_FETCH, null, null, $OrmEvent);
			ACL_ORM and sys::acl()->onOrm($this->_oid, 'FETCH', SESSION_UID);
			if($Entity = QueryRunner::fetchOne($this->pdo, $this->class, $offset, $orderExp, $OrmEvent->getCriteriaExp(), $fetchMode, $fetchSubset)) {
				sys::event(OrmEvent::EVENT_POST_FETCH, null, null, $OrmEvent->setEntity($Entity));
			}
			return $Entity;
		} catch(\PDOException $Ex) {
			throw new Exception(200, [$this->_oid, $Ex->getCode(), $Ex->getMessage()]);
		}
	}

	/**
	 * Fetch entities using a Query.
	 * @param int|null $offset OFFSET
	 * @param int|null $limit LIMIT
	 * @param string|null $orderExp ORDER BY expression
	 * @param string|null $criteriaExp CRITERIA expression
	 * @param int $fetchMode fetch mode: FETCH_OBJ, FETCH_ARRAY, FETCH_JSON
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return array entities
	 * @throws Exception
	 */
	protected function execFetchAll($offset=null, $limit=null, $orderExp=null, $criteriaExp=null, $fetchMode=self::FETCH_OBJ, $fetchSubset=null) {
		$OrmEvent = (new OrmEvent($this))->criteriaExp($criteriaExp);
		try {
			sys::event(OrmEvent::EVENT_PRE_FETCH_ALL, null, null, $OrmEvent);
			ACL_ORM and sys::acl()->onOrm($this->_oid, 'FETCH', SESSION_UID);
			if($entities = QueryRunner::fetchAll($this->pdo, $this->class, $offset,  $limit, $orderExp, $OrmEvent->getCriteriaExp(), $fetchMode, $fetchSubset)) {
				sys::event(OrmEvent::EVENT_POST_FETCH_ALL, null, null, $OrmEvent->setEntities($entities));
			}
			return $entities;
		} catch(\PDOException $Ex) {
			throw new Exception(200, [$this->_oid, $Ex->getCode(), $Ex->getMessage()]);
		}
	}

	/**
	 * Insert Entity using a Query.
	 * @param mixed $id primary key(s)
	 * @param array|object $data new Entity data, or Entity object
	 * @param bool $validate FALSE to skip validation
	 * @param int $fetchMode fetch mode (FETCH_OBJ, FETCH_ARRAY, FETCH_JSON), FALSE to skip fetch after insert
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return mixed $Entity object or array if $fetchMode, TRUE if not $fetchMode, FALSE on failure
	 * @throws Exception
	 */
	protected function execInsertOne($id, $data, $validate=true, $fetchMode=self::FETCH_OBJ, $fetchSubset=null) {
		$Metadata = Metadata::get($this->class);
		try {
			$Entity = (is_object($data)) ? $data : new $this->class($data);
			// inject primary key(s)
			if($id) {
				$Entity->__construct(array_combine($Metadata->pkeys(), (array)$id));
			}
			$OrmEvent = (new OrmEvent($this))->setEntity($Entity);
			sys::event(OrmEvent::EVENT_PRE_INSERT, null, null, $OrmEvent);
			ACL_ORM and sys::acl()->onOrm($this->_oid, 'INSERT', SESSION_UID);
			$this->_onSave->invoke($Entity);
			// validate
			if($validate) $this->doValidate($Entity, $validate);
			// run INSERT
			if(QueryRunner::insert($this->pdo, $Entity)) {
				if($fetchMode) {
					$criteriaExp = $Metadata->pkCriteria($Entity);
					$response = QueryRunner::fetchOne($this->pdo, $this->class, null, null, $criteriaExp, $fetchMode, $fetchSubset);
				} else $response = true;
				sys::event(OrmEvent::EVENT_POST_INSERT, null, null, $OrmEvent);
				return $response;
			} else return false;
		} catch(\PDOException $Ex) {
			throw new Exception(100, [$this->_oid, $Ex->getCode(), $Ex->getMessage()]);
		}
	}

	/**
	 * Update Entity using a Query.
	 * @param mixed $id primary key(s)
	 * @param array|object $data new Entity data, or Entity object
	 * @param bool $validate
	 * @param int $fetchMode fetch mode (FETCH_OBJ, FETCH_ARRAY, FETCH_JSON), FALSE to skip fetch after insert
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return mixed $Entity object or array if $fetchMode, TRUE if not $fetchMode, FALSE on failure
	 * @throws Exception
	 */
	protected function execUpdateOne($id, $data, $validate=true, $fetchMode=self::FETCH_OBJ, $fetchSubset=null) {
		$Metadata = Metadata::get($this->class);
		$criteriaExp = $Metadata->pkCriteria($id);
		if(is_object($data)) $data = DataMapper::object2array($data);
		try {
			$dbData = QueryRunner::fetchOne($this->pdo, $this->class, 0, null, $criteriaExp, self::FETCH_ARRAY);
			$newData = DataMapper::sql2array(array_merge($dbData, $data), $this->class);
			$Entity = new $this->class($newData);
			$OrmEvent = (new OrmEvent($this))->setEntity($Entity);
			sys::event(OrmEvent::EVENT_PRE_UPDATE, null, null, $OrmEvent);
			ACL_ORM and sys::acl()->onOrm($this->_oid, 'UPDATE', SESSION_UID);
			// detect changes
			$newData = DataMapper::object2sql($Entity);
			$changes = [];
			$props = $Metadata->properties();
			foreach($newData as $k=>$v) {
				if($dbData[$k] != $v && !isset($props[$k]['primarykey']) && !$props[$k]['readonly'])
					$changes[$k] = $newData[$k];
			}
			if(!count($changes)) {
				sys::trace(LOG_DEBUG, T_INFO, 'SKIP update, Entity not modified');
				return true;
			}
			// onSave callback
			$this->_onSave->invoke($Entity);
			// re-check changes after onSave()
			$newData = DataMapper::object2sql($Entity);
			$changes = [];
			$props = $Metadata->properties();
			foreach($newData as $k=>$v) {
				if($dbData[$k] != $v && !isset($props[$k]['primarykey']) && !$props[$k]['readonly'])
					$changes[$k] = $newData[$k];
			}
			// validate
			if($validate) $this->doValidate($Entity, $validate);
			// run UPDATE
			if(QueryRunner::update($this->pdo, $Entity, $changes)) {
				if($fetchMode) {
					$response = QueryRunner::fetchOne($this->pdo, $this->class, null, null, $criteriaExp, $fetchMode, $fetchSubset);
				} else $response = true;
				sys::event(OrmEvent::EVENT_POST_UPDATE, null, null, $OrmEvent);
				return $response;
			} else return false;
		} catch(\PDOException $Ex) {
			throw new Exception(300, [$this->_oid, $Ex->getCode(), $Ex->getMessage()]);
		}
	}

	/**
	 * @param $Entity
	 * @param bool|string $validateMode, TRUE or a named @orm-validate-subset
	 * @throws Exception
	 */
	protected function doValidate($Entity, $validateMode) {
		sys::trace(LOG_DEBUG, T_INFO, 'subset: '.$validateMode);
		$validateSubset = (is_string($validateMode)) ? Metadata::get($this->class)->validateSubset($validateMode) : null;
		$validateMode = (is_string($validateMode)) ? $validateMode : null;
		$errorsByTags = Validator::validate($Entity, $validateSubset);
		$errorsByFn = $this->validate($Entity, $validateMode);
		$errors = array_merge($errorsByTags, $errorsByFn);
		if(!empty($errors)) throw new Exception(500, [implode(', ',array_keys($errors))], $errors);
	}
}
class_exists('metadigit\core\db\orm\util\DataMapper');
class_exists('metadigit\core\db\orm\util\QueryRunner');
