<?php
namespace renovant\core\db\orm;
use const renovant\core\trace\T_DB;
use renovant\core\sys,
	renovant\core\authz\OrmAuthz,
	renovant\core\db\orm\util\DataMapper,
	renovant\core\db\orm\util\Metadata,
	renovant\core\db\orm\util\QueryRunner,
	renovant\core\util\validator\Validator;
class Repository {
	use \renovant\core\CoreTrait;

	/** FETCH MODE as objects */
	const FETCH_OBJ		= 1;
	/** FETCH MODE as array (with data type mapping) */
	const FETCH_ARRAY	= 2;
	/** FETCH MODE as array for JSON output (with data type mapping) */
	const FETCH_JSON	= 3;

	/** Entity class */
	protected string $class;
	/** PDO instance ID */
	protected ?string $pdo;

	protected ?OrmAuthz $OrmAuthz = null;
	protected ?Metadata $Metadata = null;

	protected $OrmEvent;

	protected ?QueryRunner $QueryRunner = null;

	/**
	 * @param string $class Entity class
	 * @param string|null $pdo PDO instance ID
	 */
	function __construct(string $class, ?string $pdo=null) {
		$this->class = $class;
		$this->pdo = $pdo;
		$this->__wakeup();
	}

	function __sleep() {
		return ['_', 'class', 'pdo'];
	}

	function __wakeup() {
		class_exists($this->class);
		$this->OrmAuthz = call_user_func($this->class.'::authz');
		$this->Metadata = call_user_func($this->class.'::metadata');
		$this->QueryRunner = new QueryRunner($this->pdo, $this->Metadata);
	}

	/**
	 * Convert entities objects to array
	 */
	function toArray(object|array $entities, ?string $subset=null): array {
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
	 */
	function toJson(object|array $entities, ?string $subset=null): array {
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
	 * @throws Exception
	 * @throws \Exception
	 */
	function count(?string $criteriaExp=null): int {
		return $this->execCount($criteriaExp);
	}

	/**
	 * Create new Entity instance
	 */
	function create(array $data=[]): object {
		return new $this->class($data);
	}

	/**
	 * Delete Entity from DB
	 * @param mixed $EntityOrKey object or its primary keys
	 * @param int|false|null $fetchMode fetch mode (FETCH_OBJ, FETCH_ARRAY, FETCH_JSON), FALSE to skip fetch before delete
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return object|array|bool $Entity object or array if $fetchMode, TRUE if not $fetchMode, FALSE on failure
	 * @throws Exception
	 * @throws \ReflectionException
	 * @throws \renovant\core\authz\AuthzException
	 * @throws \renovant\core\context\ContextException
	 * @throws \renovant\core\event\EventDispatcherException
	 */
	function delete(mixed $EntityOrKey, int|false|null $fetchMode=self::FETCH_OBJ, ?string $fetchSubset=null): object|array|bool {
		if(is_object($EntityOrKey)) {
			$Entity = $EntityOrKey;
		} else {
			$criteriaExp = $this->Metadata->pkCriteria($EntityOrKey);
			$Entity = $this->execFetchOne(0, null, $criteriaExp);
		}
		return $this->execDeleteOne($Entity, $fetchMode, $fetchSubset);
	}

	/**
	 * Delete entities by a set of Criteria and ORDER BY
	 * @throws Exception
	 * @throws \Exception
	 * @throws \renovant\core\authz\AuthzException
	 */
	function deleteAll(?int $limit, ?string $orderExp=null, ?string $criteriaExp=null): int {
		return $this->execDeleteAll($limit, $orderExp, $criteriaExp);
	}

	/**
	 * Fetch an Entity by its primary key
	 * @param mixed $id Entity primary key (single or array)
	 * @param int $fetchMode fetch mode: FETCH_OBJ, FETCH_ARRAY, FETCH_JSON
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return object|array|false Entity, false if not found
	 * @throws Exception
	 * @throws \ReflectionException
	 * @throws \renovant\core\authz\AuthzException
	 * @throws \renovant\core\context\ContextException
	 * @throws \renovant\core\event\EventDispatcherException
	 */
	function fetch(mixed $id, int $fetchMode=self::FETCH_OBJ, ?string $fetchSubset=null): object|array|false {
		$criteriaExp = $this->Metadata->pkCriteria($id);
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
	 * @throws \ReflectionException
	 * @throws \renovant\core\authz\AuthzException
	 * @throws \renovant\core\context\ContextException
	 * @throws \renovant\core\event\EventDispatcherException
	 */
	function fetchOne(?int $offset=null, ?string $orderExp=null, ?string $criteriaExp=null, int $fetchMode=self::FETCH_OBJ, ?string $fetchSubset=null): object|array|null {
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
	 * @throws \renovant\core\authz\AuthzException
	 */
	function fetchAll(?int $page=null, ?int $pageSize=null, ?string $orderExp=null, ?string $criteriaExp=null, int $fetchMode=self::FETCH_OBJ, ?string $fetchSubset=null): array {
		$offset = ($page && $pageSize) ? $pageSize * $page - $pageSize : null;
		return $this->execFetchAll($offset, $pageSize, $orderExp, $criteriaExp, $fetchMode, $fetchSubset);
	}

	/**
	 * Insert Entity into DB
	 * @param object $Entity
	 * @param bool|string $validate TRUE to validate all, a named @orm-validate-subset, or FALSE to skip validation
	 * @param int $fetchMode fetch mode (FETCH_OBJ, FETCH_ARRAY, FETCH_JSON), FALSE to skip fetch after insert
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return array|bool|object $Entity object or array if $fetchMode, TRUE if not $fetchMode, FALSE on failure
	 * @throws Exception
	 * @throws \renovant\core\authz\AuthzException
	 */
	function insert(object $Entity, string|bool $validate=true, int $fetchMode=self::FETCH_OBJ, ?string $fetchSubset=null): object|array|bool {
		return $this->execInsertOne(null, $Entity, $validate, $fetchMode, $fetchSubset);
	}

	/**
	 * Insert raw data into DB
	 * @param mixed $id primary key(s)
	 * @param array $data new Entity data
	 * @param bool|string $validate TRUE to validate all, a named @orm-validate-subset, or FALSE to skip validation
	 * @param int $fetchMode fetch mode (FETCH_OBJ, FETCH_ARRAY, FETCH_JSON), FALSE to skip fetch after insert
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return array|bool|object $Entity object or array if $fetchMode, TRUE if not $fetchMode, FALSE on failure
	 * @throws Exception
	 * @throws \renovant\core\authz\AuthzException
	 */
	function insertOne(mixed $id, array $data, string|bool $validate=true, int $fetchMode=self::FETCH_OBJ, ?string $fetchSubset=null): object|array|bool {
		return $this->execInsertOne($id, $data, $validate, $fetchMode, $fetchSubset);
	}

	/**
	 * Update Entity into DB
	 * @param object $Entity
	 * @param bool|string $validate TRUE to validate all, a named @orm-validate-subset, or FALSE to skip validation
	 * @param int $fetchMode fetch mode (FETCH_OBJ, FETCH_ARRAY, FETCH_JSON), FALSE to skip fetch after insert
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return array|bool|object $Entity object or array if $fetchMode, TRUE if not $fetchMode, FALSE on failure
	 * @throws Exception
	 * @throws \renovant\core\authz\AuthzException
	 */
	function update(object $Entity, string|bool $validate=true, int $fetchMode=self::FETCH_OBJ, ?string $fetchSubset=null): object|array|bool {
		return $this->execUpdateOne(null, $Entity, $validate, $fetchMode, $fetchSubset);
	}

	/**
	 * Update Entity into DB
	 * @param array $entities
	 * @param bool|string $validate TRUE to validate all, a named @orm-validate-subset, or FALSE to skip validation
	 * @param int $fetchMode fetch mode (FETCH_OBJ, FETCH_ARRAY, FETCH_JSON), FALSE to skip fetch after insert
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return array|bool Entity objects or array if $fetchMode, TRUE if not $fetchMode, FALSE on failure
	 * @throws Exception
	 * @throws \renovant\core\authz\AuthzException
	 */
	function updateAll(array $entities, string|bool $validate=true, int $fetchMode=self::FETCH_OBJ, ?string $fetchSubset=null): array|bool {
		$data = [];
		foreach ($entities as $Entity)
			$data[] = $this->execUpdateOne(null, $Entity, $validate, $fetchMode, $fetchSubset);
		return $data;
	}

	/**
	 * Update raw data into DB
	 * @param mixed $id primary key(s)
	 * @param array $data new Entity data
	 * @param bool|string $validate TRUE to validate all, a named @orm-validate-subset, or FALSE to skip validation
	 * @param int $fetchMode fetch mode (FETCH_OBJ, FETCH_ARRAY, FETCH_JSON), FALSE to skip fetch after insert
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return array|bool|object $Entity object or array if $fetchMode, TRUE if not $fetchMode, FALSE on failure
	 * @throws Exception
	 * @throws \renovant\core\authz\AuthzException
	 */
	function updateOne(mixed $id, array $data, string|bool $validate=true, int $fetchMode=self::FETCH_OBJ, ?string $fetchSubset=null): object|array|bool {
		return $this->execUpdateOne($id, $data, $validate, $fetchMode, $fetchSubset);
	}

	/**
	 * Validate Entity.
	 * Empty implementation, can be overridden by subclasses
	 * @param object $Entity
	 * @param string|null $validateMode
	 * @return array map of properties & error codes, empty if VALID
	 */
	function validate(object $Entity, ?string $validateMode): array {
		return [];
	}

	/**
	 * Count entities using a Query.
	 * @throws Exception
	 * @throws \ReflectionException
	 * @throws \renovant\core\authz\AuthzException
	 * @throws \renovant\core\context\ContextException
	 * @throws \renovant\core\event\EventDispatcherException
	 */
	protected function execCount(?string $criteriaExp=null): int {
		$this->OrmEvent = (new OrmEvent($this))->criteriaExp($criteriaExp);
		try {
			$this->triggerEvent(OrmEvent::EVENT_PRE_COUNT);
			$this->OrmAuthz->check(OrmAuthz::ACTION_SELECT, $this->OrmEvent);
			return $this->QueryRunner->count($this->OrmEvent->getCriteriaExp());
		} catch(\PDOException $Ex){
			throw new Exception(200, [$this->_, $Ex->getCode(), $Ex->getMessage()]);
		}
	}

	/**
	 * Delete Entity using a Query.
	 * @param mixed $Entity object or its primary keys
	 * @param int $fetchMode fetch mode (FETCH_OBJ, FETCH_ARRAY, FETCH_JSON), FALSE to skip fetch after insert
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return object|array|bool $Entity object or array if $fetchMode, TRUE if not $fetchMode, FALSE on failure
	 * @throws Exception
	 * @throws \ReflectionException
	 * @throws \renovant\core\authz\AuthzException
	 * @throws \renovant\core\context\ContextException
	 * @throws \renovant\core\event\EventDispatcherException
	 */
	protected function execDeleteOne(mixed $Entity, int $fetchMode=self::FETCH_OBJ, ?string $fetchSubset=null): object|array|bool {
		try {
			$this->OrmEvent = (new OrmEvent($this))->setEntity($Entity);
			$this->triggerEvent(OrmEvent::EVENT_PRE_DELETE);
			$this->OrmAuthz->check(OrmAuthz::ACTION_DELETE, $this->OrmEvent);
			if(!$this->QueryRunner->deleteOne($Entity, $this->OrmEvent->getCriteriaExp()))
				return false;
			if(method_exists($Entity, 'onDelete')) $Entity->onDelete();
			$this->triggerEvent(OrmEvent::EVENT_POST_DELETE);
			return match ($fetchMode) {
				self::FETCH_OBJ => $Entity,
				self::FETCH_ARRAY => DataMapper::object2array($Entity, $fetchSubset),
				self::FETCH_JSON => DataMapper::object2json($Entity, $fetchSubset),
				default => true,
			};
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
	 * @throws \renovant\core\authz\AuthzException
	 * @throws \renovant\core\context\ContextException
	 * @throws \renovant\core\event\EventDispatcherException
	 * @throws \Exception
	 */
	protected function execDeleteAll(?int $limit, ?string $orderExp=null, ?string $criteriaExp=null): int {
		$this->OrmEvent = (new OrmEvent($this))->criteriaExp($criteriaExp);
		try {
			$this->triggerEvent(OrmEvent::EVENT_PRE_DELETE_ALL);
			$this->OrmAuthz->check(OrmAuthz::ACTION_DELETE, $this->OrmEvent);
			$n = $this->QueryRunner->deleteAll($limit, $orderExp, $this->OrmEvent->getCriteriaExp());
			$this->triggerEvent(OrmEvent::EVENT_POST_DELETE_ALL);
			return $n;
		} catch(\PDOException $Ex) {
			throw new Exception(400, [$this->_, $Ex->getCode(), $Ex->getMessage()]);
		}
	}

	/**
	 * @throws Exception
	 * @throws \ReflectionException
	 * @throws \renovant\core\authz\AuthzException
	 * @throws \renovant\core\context\ContextException
	 * @throws \renovant\core\event\EventDispatcherException
	 * @throws \Exception
	 */
	protected function execFetchOne(?int $offset=null, ?string $orderExp=null, ?string $criteriaExp=null, int $fetchMode=self::FETCH_OBJ, ?string $fetchSubset=null): object|array|bool {
		$this->OrmEvent = (new OrmEvent($this))->criteriaExp($criteriaExp);
		try {
			$this->triggerEvent(OrmEvent::EVENT_PRE_FETCH);
			$this->OrmAuthz->check(OrmAuthz::ACTION_SELECT, $this->OrmEvent);
			if($Entity = $this->QueryRunner->fetchOne($this->class, $offset, $orderExp, $this->OrmEvent->getCriteriaExp(), $fetchMode, $fetchSubset)) {
				$this->triggerEvent(OrmEvent::EVENT_POST_FETCH, $Entity);
			}
			return $Entity;
		} catch(\PDOException $Ex) {
			throw new Exception(200, [$this->_, $Ex->getCode(), $Ex->getMessage()]);
		}
	}

	/**
	 * @throws Exception
	 * @throws \Exception
	 * @throws \renovant\core\authz\AuthzException
	 */
	protected function execFetchAll(?int $offset=null, ?int $limit=null, ?string $orderExp=null, ?string $criteriaExp=null, int $fetchMode=self::FETCH_OBJ, ?string $fetchSubset=null): array {
		$this->OrmEvent = (new OrmEvent($this))->criteriaExp($criteriaExp);
		try {
			$this->triggerEvent(OrmEvent::EVENT_PRE_FETCH_ALL);
			$this->OrmAuthz->check(OrmAuthz::ACTION_SELECT, $this->OrmEvent);
			if($entities = $this->QueryRunner->fetchAll($this->class, $offset,  $limit, $orderExp, $this->OrmEvent->getCriteriaExp(), $fetchMode, $fetchSubset)) {
				$this->triggerEvent(OrmEvent::EVENT_POST_FETCH_ALL, $entities);
			}
			return $entities;
		} catch(\PDOException $Ex) {
			throw new Exception(200, [$this->_, $Ex->getCode(), $Ex->getMessage()]);
		}
	}

	/**
	 * @throws Exception
	 * @throws \Exception
	 * @throws \renovant\core\authz\AuthzException
	 */
	protected function execInsertOne(mixed $id, object|array $data, string|bool $validate=true, int $fetchMode=self::FETCH_OBJ, ?string $fetchSubset=null): object|array|bool {
		try {
			$Entity = (is_object($data)) ? $data : new $this->class($data);
			// inject primary key(s)
			if($id) {
				$Entity->__construct(array_combine($this->Metadata->pKeys(), (array)$id));
			}
			$this->OrmEvent = (new OrmEvent($this))->setEntity($Entity);
			$this->triggerEvent(OrmEvent::EVENT_PRE_INSERT);
			$this->OrmAuthz->check(OrmAuthz::ACTION_INSERT, $this->OrmEvent);
			if(method_exists($Entity, 'onSave')) $Entity->onSave();
			// validate
			if($validate) $this->doValidate($Entity, $validate);
			// run INSERT & build response
			if(!$this->QueryRunner->insert($Entity))
				$response = false;
			elseif($fetchMode) {
				$criteriaExp = $this->Metadata->pkCriteria($Entity);
				$response = $Entity = $this->QueryRunner->fetchOne($this->class, null, null, $criteriaExp, $fetchMode, $fetchSubset);
			} else
				$response = true;
			if($response) $this->triggerEvent(OrmEvent::EVENT_POST_INSERT, $Entity);
			return $response;
		} catch(\PDOException $Ex) {
			throw new Exception(100, [$this->_, $Ex->getCode(), $Ex->getMessage()]);
		}
	}

	/**
	 * @throws Exception
	 * @throws \Exception
	 * @throws \renovant\core\authz\AuthzException
	 */
	protected function execUpdateOne(mixed $id, object|array $data, string|bool $validate=true, int $fetchMode=self::FETCH_OBJ, ?string $fetchSubset=null): object|array|bool {
		try {
			$this->OrmEvent = (new OrmEvent($this));
			if(is_object($data)) {
				$Entity = $data;
				$this->OrmEvent->criteriaExp($this->Metadata->pkCriteria($Entity));
				$this->OrmAuthz->check(OrmAuthz::ACTION_UPDATE, $this->OrmEvent);
			} else {
				$this->OrmEvent->criteriaExp($this->Metadata->pkCriteria($id));
				$this->OrmAuthz->check(OrmAuthz::ACTION_UPDATE, $this->OrmEvent);
				$Entity = $this->QueryRunner->fetchOne($this->class, 0, null, $this->OrmEvent->getCriteriaExp());
				$Entity($data);
			}
			$this->OrmEvent->setEntity($Entity);
			$this->triggerEvent(OrmEvent::EVENT_PRE_UPDATE);
			// onSave callback
			if(method_exists($Entity, 'onSave')) $Entity->onSave();
			// detect changes after onSave()
			$changes = $Entity::changes($Entity);
			// validate
			if($validate) $this->doValidate($Entity, $validate);
			// run UPDATE & build response
			$response = false;
			if(empty($changes)) {
				sys::trace(LOG_DEBUG, T_DB, sprintf('[%s] SKIP UPDATE `%s` WHERE %s', $this->pdo, $this->Metadata->sql('target'), $this->Metadata->pkCriteria($Entity)));
				$response = true;
			} else {
				if($this->QueryRunner->update($Entity, $changes, $this->OrmEvent->getCriteriaExp()))
					$response = true;
			}
			if($response && $fetchMode) {
//				$criteriaExp = $this->Metadata->pkCriteria($Entity);
				$response = $Entity = $this->QueryRunner->fetchOne($this->class, null, null, $this->OrmEvent->getCriteriaExp(), $fetchMode, $fetchSubset);
			}
			if(!empty($changes)) $this->triggerEvent(OrmEvent::EVENT_POST_UPDATE, $Entity);
			return $response;
		} catch(\PDOException $Ex) {
			throw new Exception(300, [$this->_, $Ex->getCode(), $Ex->getMessage()]);
		}
	}

	/**
	 * @throws Exception
	 * @throws \ReflectionException
	 */
	protected function doValidate(object $Entity, string|bool $validateMode) {
		$validateSubset = (is_string($validateMode)) ? $this->Metadata->validateSubset($validateMode) : null;
		$validateMode = (is_string($validateMode)) ? $validateMode : null;
		$errorsByTags = Validator::validate($Entity, $validateSubset);
		$errorsByFn = $this->validate($Entity, $validateMode);
		$errors = array_merge($errorsByTags, $errorsByFn);
		if(!empty($errors)) throw new Exception(500, [implode(', ',array_keys($errors))], $errors);
	}

	/**
	 * @throws \ReflectionException
	 * @throws \renovant\core\context\ContextException
	 * @throws \renovant\core\event\EventDispatcherException
	 */
	protected function triggerEvent(string $eventName, mixed $param=null) {
		if($name = $this->Metadata->event($eventName)) {
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
class_exists('renovant\core\db\orm\util\Metadata');
class_exists('renovant\core\db\orm\util\QueryRunner');
