<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\db\orm;
use const renovant\core\trace\T_DB;
use renovant\core\sys,
	renovant\core\db\orm\util\DataMapper,
	renovant\core\db\orm\util\QueryRunner,
	renovant\core\util\validator\Validator;
/**
 * ORM Repository
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
class Repository {
	use \renovant\core\CoreTrait;
	const ACL_SKIP = true;

	/** FETCH MODE as objects */
	const FETCH_OBJ		= 1;
	/** FETCH MODE as array (with data type mapping) */
	const FETCH_ARRAY	= 2;
	/** FETCH MODE as array for JSON output (with data type mapping) */
	const FETCH_JSON	= 3;

	const META_CRITERIA			= 'CRITERIA';
	const META_EVENTS			= 'EVENTS';
	const META_FETCH_ORDERBY	= 'FETCH_ORDERBY';
	const META_FETCH_SUBSETS	= 'FETCH_SUBSETS';
	const META_PROPS			= 'PROPS';
	const META_PKEYS			= 'PKEYS';
	const META_PKCRITERIA		= 'PKCRITERIA';
	const META_SQL				= 'SQL';
	const META_VALIDATE_SUBSETS	= 'VALIDATE_SUBSETS';

	/** Entity class
	 * @var string */
	protected $class;
	/** PDO instance ID
	 * @var string */
	protected $pdo = 'master';

	protected $OrmEvent;

	/**
	 * @param string $class Entity class
	 * @param string|null $pdo PDO instance ID, default to "master"
	 */
	function __construct(string $class, ?string $pdo='master') {
		$this->class = $class;
		$this->pdo = $pdo;
		$this->__wakeup();
	}

	function __sleep() {
		return ['_', 'class', 'pdo'];
	}

	function __wakeup() {
		class_exists($this->class);
	}

	/**
	 * Convert entities objects to array
	 * @param object|array $entities one or more Entities
	 * @param string|null $subset
	 * @return array
	 */
	function toArray($entities, ?string $subset=null): array {
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
	 * Convert entities objects to JSON array
	 * @param object|array $entities one or more Entities
	 * @param string|null $subset
	 * @return array
	 */
	function toJson($entities, ?string $subset=null): array {
		$data = [];
		if(is_array($entities)) {
			foreach($entities as $Entity) {
				$data[] = DataMapper::object2json($Entity, $subset);
			}
		} elseif(is_object($entities))
			$data = DataMapper::object2json($entities, $subset);
		else trigger_error('Invalid data');
		return $data;
	}

	/**
	 * Count entities by a set of Criteria
	 * @param string|null $criteriaExp CRITERIA expression
	 * @return integer
	 * @throws Exception
	 * @throws \Exception
	 */
	function count($criteriaExp=null): int {
		return $this->execCount($criteriaExp);
	}

	/**
	 * Create new Entity instance
	 * @param array $data Entity data
	 * @return object Entity
	 */
	function create(array $data=[]): object {
		return new $this->class($data);
	}

	/**
	 * Delete Entity from DB
	 * @param mixed $EntityOrKey object or its primary keys
	 * @param int $fetchMode fetch mode (FETCH_OBJ, FETCH_ARRAY, FETCH_JSON), FALSE to skip fetch before delete
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return mixed $Entity object or array if $fetchMode, TRUE if not $fetchMode, FALSE on failure
	 * @throws Exception
	 * @throws \Exception
	 */
	function delete($EntityOrKey, $fetchMode=self::FETCH_OBJ, $fetchSubset=null) {
		if(is_object($EntityOrKey)) {
			$Entity = $EntityOrKey;
		} else {
			$criteriaExp = call_user_func($this->class.'::metadata', self::META_PKCRITERIA, $EntityOrKey);
			$Entity = $this->execFetchOne(0, null, $criteriaExp);
		}
		return $this->execDeleteOne($Entity, $fetchMode, $fetchSubset);
	}

	/**
	 * Fetch an Entity by a set of Criteria and ORDER BY
	 * @param integer|null $limit LIMIT
	 * @param string|null $orderExp ORDER BY expression
	 * @param string|null $criteriaExp CRITERIA expression
	 * @return integer n° of deleted entities
	 * @throws Exception
	 * @throws \Exception
	 */
	function deleteAll(?int $limit, $orderExp=null, $criteriaExp=null) {
		return $this->execDeleteAll($limit, $orderExp, $criteriaExp);
	}

	/**
	 * Fetch an Entity by its primary key
	 * @param mixed $id Entity primary key (single or array)
	 * @param int $fetchMode fetch mode: FETCH_OBJ, FETCH_ARRAY, FETCH_JSON
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return object|false Entity, false if not found
	 * @throws Exception
	 * @throws \Exception
	 */
	function fetch($id, int $fetchMode=self::FETCH_OBJ, ?string $fetchSubset=null) {
		$criteriaExp = call_user_func($this->class.'::metadata', self::META_PKCRITERIA, $id);
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
	 * @throws \Exception
	 */
	function fetchOne(?int $offset=null, ?string $orderExp=null, ?string $criteriaExp=null, int $fetchMode=self::FETCH_OBJ, ?string $fetchSubset=null) {
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
	 * @throws \Exception
	 */
	function fetchAll(?int $page=null, ?int $pageSize=null, ?string $orderExp=null, ?string $criteriaExp=null, int $fetchMode=self::FETCH_OBJ, ?string $fetchSubset=null) {
		$offset = ($page && $pageSize) ? $pageSize * $page - $pageSize : null;
		return $this->execFetchAll($offset, $pageSize, $orderExp, $criteriaExp, $fetchMode, $fetchSubset);
	}

	/**
	 * Insert Entity into DB
	 * @param object $Entity
	 * @param bool|string $validate TRUE to validate all, a named @orm-validate-subset, or FALSE to skip validation
	 * @param int $fetchMode fetch mode (FETCH_OBJ, FETCH_ARRAY, FETCH_JSON), FALSE to skip fetch after insert
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return mixed $Entity object or array if $fetchMode, TRUE if not $fetchMode, FALSE on failure
	 * @throws Exception
	 * @throws \Exception
	 */
	function insert(object $Entity, $validate=true, int $fetchMode=self::FETCH_OBJ, ?string $fetchSubset=null) {
		return $this->execInsertOne(null, $Entity, $validate, $fetchMode, $fetchSubset);
	}

	/**
	 * Insert raw data into DB
	 * @param mixed $id primary key(s)
	 * @param array $data new Entity data
	 * @param bool|string $validate TRUE to validate all, a named @orm-validate-subset, or FALSE to skip validation
	 * @param int $fetchMode fetch mode (FETCH_OBJ, FETCH_ARRAY, FETCH_JSON), FALSE to skip fetch after insert
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return mixed $Entity object or array if $fetchMode, TRUE if not $fetchMode, FALSE on failure
	 * @throws Exception
	 */
	function insertOne($id, array $data, $validate=true, int $fetchMode=self::FETCH_OBJ, ?string $fetchSubset=null) {
		return $this->execInsertOne($id, $data, $validate, $fetchMode, $fetchSubset);

	}

	/**
	 * Update Entity into DB
	 * @param object $Entity
	 * @param bool|string $validate TRUE to validate all, a named @orm-validate-subset, or FALSE to skip validation
	 * @param int $fetchMode fetch mode (FETCH_OBJ, FETCH_ARRAY, FETCH_JSON), FALSE to skip fetch after insert
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return mixed $Entity object or array if $fetchMode, TRUE if not $fetchMode, FALSE on failure
	 * @throws Exception
	 * @throws \Exception
	 */
	function update(object $Entity, $validate=true, int $fetchMode=self::FETCH_OBJ, ?string $fetchSubset=null) {
		return $this->execUpdateOne(null, $Entity, $validate, $fetchMode, $fetchSubset);
	}

	/**
	 * Update raw data into DB
	 * @param mixed $id primary key(s)
	 * @param array $data new Entity data
	 * @param bool|string $validate TRUE to validate all, a named @orm-validate-subset, or FALSE to skip validation
	 * @param int $fetchMode fetch mode (FETCH_OBJ, FETCH_ARRAY, FETCH_JSON), FALSE to skip fetch after insert
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return mixed $Entity object or array if $fetchMode, TRUE if not $fetchMode, FALSE on failure
	 * @throws Exception
	 * @throws \Exception
	 */
	function updateOne($id, array $data, $validate=true, int $fetchMode=self::FETCH_OBJ, ?string $fetchSubset=null) {
		return $this->execUpdateOne($id, $data, $validate, $fetchMode, $fetchSubset);
	}

	/**
	 * Validate Entity.
	 * Empty implementation, can be overridden by subclasses
	 * @param $Entity
	 * @param string|null $validateMode
	 * @return array map of properties & error codes, empty if VALID
	 */
	function validate(object $Entity, ?string $validateMode) {
		return [];
	}

	/**
	 * Count entities using a Query.
	 * @param string|null $criteriaExp CRITERIA expression
	 * @return integer
	 * @throws Exception
	 * @throws \Exception
	 */
	protected function execCount(?string $criteriaExp=null) {
		$this->OrmEvent = (new OrmEvent($this))->criteriaExp($criteriaExp);
		try {
			$this->triggerEvent(OrmEvent::EVENT_PRE_COUNT);
			defined('SYS_ACL_ORM') and sys::acl()->onOrm($this->_, 'COUNT', sys::auth()->UID());
			return QueryRunner::count($this->pdo, $this->class, $this->OrmEvent->getCriteriaExp());
		} catch(\PDOException $Ex){
			throw new Exception(200, [$this->_, $Ex->getCode(), $Ex->getMessage()]);
		}
	}

	/**
	 * Delete Entity using a Query.
	 * @param mixed $Entity object or its primary keys
	 * @param int $fetchMode fetch mode (FETCH_OBJ, FETCH_ARRAY, FETCH_JSON), FALSE to skip fetch after insert
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return mixed $Entity object or array if $fetchMode, TRUE if not $fetchMode, FALSE on failure
	 * @throws Exception
	 * @throws \Exception
	 */
	protected function execDeleteOne($Entity, int $fetchMode=self::FETCH_OBJ, ?string $fetchSubset=null) {
		try {
			$this->OrmEvent = (new OrmEvent($this))->setEntity($Entity);
			$this->triggerEvent(OrmEvent::EVENT_PRE_DELETE);
			defined('SYS_ACL_ORM') and sys::acl()->onOrm($this->_, 'DELETE', sys::auth()->UID());
			if(!QueryRunner::deleteOne($this->pdo, $this->class, $Entity, $this->OrmEvent->getCriteriaExp()))
				return false;
			if(method_exists($Entity, 'onDelete')) $Entity->onDelete();
			$this->triggerEvent(OrmEvent::EVENT_POST_DELETE);
			switch($fetchMode) {
				case self::FETCH_OBJ: return $Entity;
				case self::FETCH_ARRAY: return DataMapper::object2array($Entity, $fetchSubset);
				case self::FETCH_JSON: return DataMapper::object2json($Entity, $fetchSubset);
				default: return true;
			}
		} catch(\PDOException $Ex) {
			throw new Exception(400, [$this->_, $Ex->getCode(), $Ex->getMessage()]);
		}
	}

	/**
	 * Delete entities using a Query.
	 * @param int|null $limit LIMIT
	 * @param string|null $orderExp ORDER BY expression
	 * @param string|null $criteriaExp CRITERIA expression
	 * @return integer n° of deleted entities
	 * @throws Exception
	 * @throws \ReflectionException
	 * @throws \renovant\core\context\ContextException
	 * @throws \renovant\core\event\EventDispatcherException
	 * @throws \Exception
	 */
	protected function execDeleteAll(?int $limit, ?string $orderExp=null, ?string $criteriaExp=null) {
		$this->OrmEvent = (new OrmEvent($this))->criteriaExp($criteriaExp);
		try {
			$this->triggerEvent(OrmEvent::EVENT_PRE_DELETE_ALL);
			defined('SYS_ACL_ORM') and sys::acl()->onOrm($this->_, 'DELETE', sys::auth()->UID());
			$n = QueryRunner::deleteAll($this->pdo, $this->class, $limit, $orderExp, $this->OrmEvent->getCriteriaExp());
			$this->triggerEvent(OrmEvent::EVENT_POST_DELETE_ALL);
			return $n;
		} catch(\PDOException $Ex) {
			throw new Exception(400, [$this->_, $Ex->getCode(), $Ex->getMessage()]);
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
	 * @throws \Exception
	 */
	protected function execFetchOne(?int $offset=null, ?string $orderExp=null, ?string $criteriaExp=null, int $fetchMode=self::FETCH_OBJ, ?string $fetchSubset=null) {
		$this->OrmEvent = (new OrmEvent($this))->criteriaExp($criteriaExp);
		try {
			$this->triggerEvent(OrmEvent::EVENT_PRE_FETCH);
			defined('SYS_ACL_ORM') and sys::acl()->onOrm($this->_, 'FETCH', sys::auth()->UID());
			if($Entity = QueryRunner::fetchOne($this->pdo, $this->class, $offset, $orderExp, $this->OrmEvent->getCriteriaExp(), $fetchMode, $fetchSubset)) {
				$this->triggerEvent(OrmEvent::EVENT_POST_FETCH, $Entity);
			}
			return $Entity;
		} catch(\PDOException $Ex) {
			throw new Exception(200, [$this->_, $Ex->getCode(), $Ex->getMessage()]);
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
	 * @throws \Exception
	 */
	protected function execFetchAll(?int $offset=null, ?int $limit=null, ?string $orderExp=null, ?string $criteriaExp=null, $fetchMode=self::FETCH_OBJ, ?string $fetchSubset=null) {
		$this->OrmEvent = (new OrmEvent($this))->criteriaExp($criteriaExp);
		try {
			$this->triggerEvent(OrmEvent::EVENT_PRE_FETCH_ALL);
			defined('SYS_ACL_ORM') and sys::acl()->onOrm($this->_, 'FETCH', sys::auth()->UID());
			if($entities = QueryRunner::fetchAll($this->pdo, $this->class, $offset,  $limit, $orderExp, $this->OrmEvent->getCriteriaExp(), $fetchMode, $fetchSubset)) {
				$this->triggerEvent(OrmEvent::EVENT_POST_FETCH_ALL, $entities);
			}
			return $entities;
		} catch(\PDOException $Ex) {
			throw new Exception(200, [$this->_, $Ex->getCode(), $Ex->getMessage()]);
		}
	}

	/**
	 * Insert Entity using a Query.
	 * @param mixed $id primary key(s)
	 * @param array|object $data new Entity data, or Entity object
	 * @param bool|string $validate TRUE to validate all, a named @orm-validate-subset, or FALSE to skip validation
	 * @param int $fetchMode fetch mode (FETCH_OBJ, FETCH_ARRAY, FETCH_JSON), FALSE to skip fetch after insert
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return mixed $Entity object or array if $fetchMode, TRUE if not $fetchMode, FALSE on failure
	 * @throws Exception
	 * @throws \Exception
	 */
	protected function execInsertOne($id, $data, $validate=true, int $fetchMode=self::FETCH_OBJ, ?string $fetchSubset=null) {
		try {
			$Entity = (is_object($data)) ? $data : new $this->class($data);
			// inject primary key(s)
			if($id) {
				$Entity->__construct(array_combine(call_user_func($this->class.'::metadata', self::META_PKEYS), (array)$id));
			}
			$this->OrmEvent = (new OrmEvent($this))->setEntity($Entity);
			$this->triggerEvent(OrmEvent::EVENT_PRE_INSERT);
			defined('SYS_ACL_ORM') and sys::acl()->onOrm($this->_, 'INSERT', sys::auth()->UID());
			if(method_exists($Entity, 'onSave')) $Entity->onSave();
			// validate
			if($validate) $this->doValidate($Entity, $validate);
			// run INSERT & build response
			if(!QueryRunner::insert($this->pdo, $Entity))
				$response = false;
			elseif($fetchMode) {
				$criteriaExp = $Entity::metadata(self::META_PKCRITERIA, $Entity);
				$response = $Entity = QueryRunner::fetchOne($this->pdo, $this->class, null, null, $criteriaExp, $fetchMode, $fetchSubset);
			} else
				$response = true;
			$this->triggerEvent(OrmEvent::EVENT_POST_INSERT, $Entity);
			return $response;
		} catch(\PDOException $Ex) {
			throw new Exception(100, [$this->_, $Ex->getCode(), $Ex->getMessage()]);
		}
	}

	/**
	 * Update Entity using a Query.
	 * @param mixed $id primary key(s)
	 * @param array|object $data new Entity data, or Entity object
	 * @param bool|string $validate TRUE to validate all, a named @orm-validate-subset, or FALSE to skip validation
	 * @param int $fetchMode fetch mode (FETCH_OBJ, FETCH_ARRAY, FETCH_JSON), FALSE to skip fetch after insert
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return mixed $Entity object or array if $fetchMode, TRUE if not $fetchMode, FALSE on failure
	 * @throws Exception
	 * @throws \Exception
	 */
	protected function execUpdateOne($id, $data, $validate=true, int $fetchMode=self::FETCH_OBJ, ?string $fetchSubset=null) {
		try {
			if(is_object($data)) {
				$Entity = $data;
			} else {
				$criteriaExp = call_user_func($this->class.'::metadata', self::META_PKCRITERIA, $id);
				$Entity = QueryRunner::fetchOne($this->pdo, $this->class, 0, null, $criteriaExp);
				$Entity($data);
			}
			$this->OrmEvent = (new OrmEvent($this))->setEntity($Entity);
			$this->triggerEvent(OrmEvent::EVENT_PRE_UPDATE);
			defined('SYS_ACL_ORM') and sys::acl()->onOrm($this->_, 'UPDATE', sys::auth()->UID());
			// onSave callback
			if(method_exists($Entity, 'onSave')) $Entity->onSave();
			// detect changes after onSave()
			$changes = $Entity::changes($Entity);
			// validate
			if($validate) $this->doValidate($Entity, $validate);
			// run UPDATE & build response
			$response = false;
			if(empty($changes)) {
				sys::trace(LOG_DEBUG, T_DB, sprintf('[%s] SKIP UPDATE `%s` WHERE %s', $this->pdo, $Entity::metadata(self::META_SQL, 'target'), $Entity::metadata(self::META_PKCRITERIA, $Entity)));
				$response = true;
			} elseif(QueryRunner::update($this->pdo, $Entity, $changes))
				$response = true;
			if($response && $fetchMode) {
				$criteriaExp = $Entity::metadata(self::META_PKCRITERIA, $Entity);
				$response = $Entity = QueryRunner::fetchOne($this->pdo, $this->class, null, null, $criteriaExp, $fetchMode, $fetchSubset);
			}
			$this->triggerEvent(OrmEvent::EVENT_POST_UPDATE, $Entity);
			return $response;
		} catch(\PDOException $Ex) {
			throw new Exception(300, [$this->_, $Ex->getCode(), $Ex->getMessage()]);
		}
	}

	/**
	 * @param $Entity
	 * @param bool|string $validateMode, TRUE or a named @orm-validate-subset
	 * @throws Exception|\ReflectionException
	 */
	protected function doValidate(object $Entity, $validateMode) {
		$validateSubset = (is_string($validateMode)) ? $Entity::metadata(self::META_VALIDATE_SUBSETS, $validateMode) : null;
		$validateMode = (is_string($validateMode)) ? $validateMode : null;
		$errorsByTags = Validator::validate($Entity, $validateSubset);
		$errorsByFn = $this->validate($Entity, $validateMode);
		$errors = array_merge($errorsByTags, $errorsByFn);
		if(!empty($errors)) throw new Exception(500, [implode(', ',array_keys($errors))], $errors);
	}

	/**
	 * @param $eventName
	 * @param mixed $param
	 * @throws \ReflectionException
	 * @throws \renovant\core\context\ContextException
	 * @throws \renovant\core\event\EventDispatcherException
	 */
	protected function triggerEvent($eventName, $param=null) {
		if($name = call_user_func($this->class.'::metadata', self::META_EVENTS, $eventName)) {
			if(is_object($param)) $this->OrmEvent->setEntity($param);
			elseif(is_array($param)) $this->OrmEvent->setEntities($param);
			sys::event(is_string($name) ? $name : $eventName, $this->OrmEvent);
		}
	}
}
class_exists('renovant\core\db\PDO');
class_exists('renovant\core\db\PDOStatement');
class_exists('renovant\core\db\Query');
class_exists('renovant\core\db\orm\OrmEvent');
class_exists('renovant\core\db\orm\util\DataMapper');
class_exists('renovant\core\db\orm\util\DataMapper');
class_exists('renovant\core\db\orm\util\QueryRunner');
