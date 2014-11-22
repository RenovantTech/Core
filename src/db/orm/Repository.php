<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\db\orm;
use metadigit\core\Kernel,
	metadigit\core\context\Context,
	metadigit\core\db\orm\util\DataMapper,
	metadigit\core\db\orm\util\QueryRunner,
	metadigit\core\util\validator\Validator;
/**
 * ORM Repository
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Repository implements \metadigit\core\context\ContextAwareInterface {
	use \metadigit\core\CoreTrait;

	/** FETCH MODE as objects */
	const FETCH_OBJ		= 1;
	/** FETCH MODE as array (with data type mapping) */
	const FETCH_ARRAY	= 2;
	/** FETCH MODE as array for JSON output (with data type mapping) */
	const FETCH_JSON	= 3;
	/** Entity class
	 * @var string */
	protected $class;
	/** owner Context
	 * @var \metadigit\core\context\Context */
	protected $Context;
	/** Entity errors
	 * @var array */
	protected $errors;
	/** Entity ORM metadata
	 * @var Metadata */
	protected $Metadata;
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
		$this->Metadata = new Metadata($class);
		$this->_onInit = new \ReflectionMethod($class, 'onInit');
		$this->_onInit->setAccessible(true);
		$this->_onSave = new \ReflectionMethod($class, 'onSave');
		$this->_onSave->setAccessible(true);
		$this->_onDelete = new \ReflectionMethod($class, 'onDelete');
		$this->_onDelete->setAccessible(true);
	}

	function __sleep() {
		return ['_oid', 'class', 'Metadata', 'pdo'];
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
	 * Returns array of invalid fields.
	 * @return array
	 */
	function getErrors() {
		return $this->errors;
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
				$data[] = DataMapper::object2array($Entity, $this->Metadata, $subset);
			}
		} elseif(is_object($entities))
			$data = DataMapper::object2array($entities, $this->Metadata, $subset);
		else trigger_error('Invalid data');
		return $data;
	}

	/**
	 * Count entities by a set of Criteria
	 * @param string|null $criteriaExp CRITERIA expression
	 * @return integer
	 */
	function count($criteriaExp=null) {
		return $this->execCount(__FUNCTION__, $criteriaExp);
	}

	/**
	 * Create new Entity instance
	 * @param array $data Entity data
	 * @return object Entity
	 */
	function create(array $data=[]) {
		return DataMapper::array2object($this->class, $data, $this->Metadata);
	}

	/**
	 * Delete Entity from DB
	 * @param mixed $EntityOrKey object or its primary keys
	 * @return boolean TRUE on success
	 * @throws Exception
	 */
	function delete($EntityOrKey) {
		if(is_object($EntityOrKey)) {
			$Entity = $EntityOrKey;
		} else {
			$criteriaExp = $this->Metadata->pkCriteria($EntityOrKey);
			$Entity = $this->execFetchOne(__FUNCTION__, 0, null, $criteriaExp);
		}
		return $this->execDeleteOne(__FUNCTION__, $Entity);
	}

	/**
	 * Fetch an Entity by a set of Criteria and ORDER BY
	 * @param integer $limit LIMIT
	 * @param string|null $orderExp ORDER BY expression
	 * @param string|null $criteriaExp CRITERIA expression
	 * @return integer n° of deleted entities
	 */
	function deleteAll($limit, $orderExp=null, $criteriaExp=null) {
		return $this->execDeleteAll(__FUNCTION__, $limit, $orderExp, $criteriaExp);
	}

	/**
	 * Fetch an Entity by its primary key
	 * @param mixed $id Entity primary key (single or array)
	 * @param int $fetchMode fetch mode: FETCH_OBJ, FETCH_ARRAY, FETCH_RAW
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return object|false Entity, false if not found
	 */
	function fetch($id, $fetchMode=self::FETCH_OBJ, $fetchSubset=null) {
		$criteriaExp = $this->Metadata->pkCriteria($id);
		return $this->execFetchOne(__FUNCTION__, 0, null, $criteriaExp, $fetchMode, $fetchSubset);
	}

	/**
	 * Fetch an Entity by a set of Criteria and ORDER BY
	 * @param integer|null $offset (starting from 1)
	 * @param string|null $orderExp ORDER BY expression
	 * @param string|null $criteriaExp CRITERIA expression
	 * @param int $fetchMode fetch mode: FETCH_OBJ, FETCH_ARRAY, FETCH_RAW
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return object|array|null Entity, NULL if not found
	 */
	function fetchOne($offset, $orderExp=null, $criteriaExp=null, $fetchMode=self::FETCH_OBJ, $fetchSubset=null) {
		return $this->execFetchOne(__FUNCTION__, $offset-1, $orderExp, $criteriaExp, $fetchMode, $fetchSubset);
	}

	/**
	 * Fetch an array of entities by a set of Criteria and ORDER BY
	 * @param int $page page n°
	 * @param int $pageSize page size
	 * @param string|null $orderExp ORDER BY expression
	 * @param string|null $criteriaExp CRITERIA expression
	 * @param int $fetchMode fetch mode: FETCH_OBJ, FETCH_ARRAY, FETCH_RAW
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return array
	 */
	function fetchAll($page, $pageSize, $orderExp=null, $criteriaExp=null, $fetchMode=self::FETCH_OBJ, $fetchSubset=null) {
		$offset = $pageSize * $page - $pageSize;
		return $this->execFetchAll(__FUNCTION__, $offset, $pageSize, $orderExp, $criteriaExp, $fetchMode, $fetchSubset);
	}

	/**
	 * Insert Entity into DB
	 * Options:
	 * - fetch: boolean, default TRUE to re-fetch Entity from DB after INSERT
	 * - validate: boolean, default TRUE to verify validation rules, FALSE to skip
	 * @param mixed $EntityOrKey object or its primary keys
	 * @param array $data new Entity data
	 * @param bool $validate FALSE to skip validation
	 * @param int $fetchMode fetch mode (FETCH_OBJ, FETCH_ARRAY, FETCH_RAW), FALSE to skip fetch after insert
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @throws Exception
	 * @return mixed $Entity object or array if $fetchMode, TRUE if not $fetchMode, FALSE on failure
	 */
	function insert($EntityOrKey, array $data=[], $validate=true, $fetchMode=self::FETCH_OBJ, $fetchSubset=null) {
		if(is_object($EntityOrKey)) {
			$Entity = $EntityOrKey;
		} else {
			$Entity = new $this->class();
			if(is_array($EntityOrKey)) {
				foreach($this->Metadata->pkeys() as $i=>$k)
					$Entity->$k = $EntityOrKey[$i];
			} else {
				$k = $this->Metadata->pkeys()[0];
				$Entity->$k = $EntityOrKey;
			}
		}
		return $this->execInsertOne(__FUNCTION__, $Entity, $data, $validate, $fetchMode, $fetchSubset);
	}

	/**
	 * Update Entity into DB
	 * Options:
	 * - fetch: boolean, default TRUE to re-fetch Entity from DB after UPDATE
	 * - validate: boolean, default TRUE to verify validation rules, FALSE to skip
	 * @param mixed $EntityOrKey object or its primary keys
	 * @param array $data new Entity data
	 * @param bool $validate FALSE to skip validation
	 * @param int $fetchMode fetch mode (FETCH_OBJ, FETCH_ARRAY, FETCH_RAW), FALSE to skip fetch after insert
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset	 * @return object|false $Entity, FALSE on failure
	 * @throws Exception
	 * @return mixed $Entity object or array if $fetchMode, TRUE if not $fetchMode, FALSE on failure
	 */
	function update($EntityOrKey, array $data=[], $validate=true, $fetchMode=self::FETCH_OBJ, $fetchSubset=null) {
		if(is_object($EntityOrKey)) {
			$Entity = $EntityOrKey;
		} else {
			$criteriaExp = $this->Metadata->pkCriteria($EntityOrKey);
			$Entity = $this->execFetchOne(__FUNCTION__, 0, null, $criteriaExp);
		}
		return $this->execUpdateOne(__FUNCTION__, $Entity, $data, $validate, $fetchMode, $fetchSubset);
	}

	/**
	 * Count entities using a Query.
	 * @param string $method count method (used for trace)
	 * @param string|null $criteriaExp CRITERIA expression
	 * @return integer
	 * @throws Exception
	 */
	protected function execCount($method, $criteriaExp=null) {
		$OrmEvent = (new OrmEvent($this))->criteriaExp($criteriaExp);
		try {
			$this->Context->trigger(OrmEvent::EVENT_PRE_COUNT, null, null, $OrmEvent);
			return QueryRunner::count($this->pdo, $this->Metadata, $OrmEvent->getCriteriaExp());
		} catch(\PDOException $Ex){
			throw new Exception(200, $this->_oid, $method, $Ex->getCode(), $Ex->getMessage());
		}
	}

	/**
	 * Delete Entity using a Query.
	 * @param string $method fetch method (used for trace)
	 * @param mixed $Entity object or its primary keys
	 * @return boolean TRUE on success
	 * @throws Exception
	 */
	protected function execDeleteOne($method, $Entity) {
		$OrmEvent = (new OrmEvent($this))->setEntity($Entity);
		try {
			$this->Context->trigger(OrmEvent::EVENT_PRE_DELETE, null, null, $OrmEvent);
			if(QueryRunner::deleteOne($this->pdo, $this->Metadata, $Entity, $OrmEvent->getCriteriaExp())) {
				$this->_onDelete->invoke($Entity);
				$this->Context->trigger(OrmEvent::EVENT_POST_DELETE, null, null, $OrmEvent);
				return true;
			} else return false;
		} catch(\PDOException $Ex) {
			throw new Exception(400, $this->_oid, $method, $Ex->getCode(), $Ex->getMessage());
		}
	}

	/**
	 * Delete entities using a Query.
	 * @param string $method fetch method (used for trace)
	 * @param int $limit LIMIT
	 * @param string|null $orderExp ORDER BY expression
	 * @param string|null $criteriaExp CRITERIA expression
	 * @return integer n° of deleted entities
	 * @throws Exception
	 */
	protected function execDeleteAll($method, $limit, $orderExp=null, $criteriaExp=null) {
		$OrmEvent = (new OrmEvent($this))->criteriaExp($criteriaExp);
		try {
			$this->Context->trigger(OrmEvent::EVENT_PRE_DELETE_ALL, null, null, $OrmEvent);
			$n = QueryRunner::deleteAll($this->pdo, $this->Metadata, $limit, $orderExp, $OrmEvent->getCriteriaExp());
			$this->Context->trigger(OrmEvent::EVENT_POST_DELETE_ALL, null, null, $OrmEvent);
			return $n;
		} catch(\PDOException $Ex) {
			throw new Exception(400, $this->_oid, $method, $Ex->getCode(), $Ex->getMessage());
		}
	}

	/**
	 * Fetch an Entity using a Query.
	 * @param string $method fetch method (used for trace)
	 * @param integer $offset OFFSET
	 * @param string|null $orderExp ORDER BY expression
	 * @param string|null $criteriaExp CRITERIA expression
	 * @param int $fetchMode fetch mode: FETCH_OBJ, FETCH_ARRAY, FETCH_RAW
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @throws Exception
	 * @return object Entity
	 */
	protected function execFetchOne($method, $offset, $orderExp=null, $criteriaExp=null, $fetchMode=self::FETCH_OBJ, $fetchSubset=null) {
		$OrmEvent = (new OrmEvent($this))->criteriaExp($criteriaExp);
		try {
			$this->Context->trigger(OrmEvent::EVENT_PRE_FETCH, null, null, $OrmEvent);
			if($Entity = QueryRunner::fetchOne($this->pdo, $this->Metadata, $this->class, $offset, $orderExp, $OrmEvent->getCriteriaExp(), $fetchMode, $fetchSubset)) {
				$this->Context->trigger(OrmEvent::EVENT_POST_FETCH, null, null, $OrmEvent->setEntity($Entity));
			}
			return $Entity;
		} catch(\PDOException $Ex) {
			throw new Exception(200, $this->_oid, $method, $Ex->getCode(), $Ex->getMessage());
		}
	}

	/**
	 * Fetch entities using a Query.
	 * @param string $method fetch method (used for trace)
	 * @param int $offset OFFSET
	 * @param int $limit LIMIT
	 * @param string|null $orderExp ORDER BY expression
	 * @param string|null $criteriaExp CRITERIA expression
	 * @param int $fetchMode fetch mode: FETCH_OBJ, FETCH_ARRAY, FETCH_RAW
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return array entities
	 * @throws Exception
	 */
	protected function execFetchAll($method, $offset, $limit, $orderExp=null, $criteriaExp=null, $fetchMode=self::FETCH_OBJ, $fetchSubset=null) {
		$OrmEvent = (new OrmEvent($this))->criteriaExp($criteriaExp);
		try {
			$this->Context->trigger(OrmEvent::EVENT_PRE_FETCH_ALL, null, null, $OrmEvent);
			if($entities = QueryRunner::fetchAll($this->pdo, $this->Metadata, $this->class, $offset,  $limit, $orderExp, $OrmEvent->getCriteriaExp(), $fetchMode, $fetchSubset)) {
				$this->Context->trigger(OrmEvent::EVENT_POST_FETCH_ALL, null, null, $OrmEvent->setEntities($entities));
			}
			return $entities;
		} catch(\PDOException $Ex) {
			throw new Exception(200, $this->_oid, $method, $Ex->getCode(), $Ex->getMessage());
		}
	}

	/**
	 * Insert Entity using a Query.
	 * @param string $method fetch method (used for trace)
	 * @param object $Entity Entity
	 * @param array $data new Entity data
	 * @param bool $validate FALSE to skip validation
	 * @param int $fetchMode fetch mode (FETCH_OBJ, FETCH_ARRAY, FETCH_RAW), FALSE to skip fetch after insert
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @throws Exception
	 * @return mixed $Entity object or array if $fetchMode, TRUE if not $fetchMode, FALSE on failure
	 */
	protected function execInsertOne($method, $Entity, $data, $validate=true, $fetchMode=self::FETCH_OBJ, $fetchSubset=null) {
		$OrmEvent = (new OrmEvent($this))->setEntity($Entity);
		DataMapper::array2object($Entity, $data, $this->Metadata);
		try {
			$this->Context->trigger(OrmEvent::EVENT_PRE_INSERT, null, null, $OrmEvent);
			$this->_onSave->invoke($Entity);
			if($validate) {
				$this->errors = Validator::validate($Entity);
				if(!empty($this->errors)) return false;
			}
			if(QueryRunner::insert($this->pdo, $this->Metadata, $Entity)) {
				if($fetchMode) {
					foreach($this->Metadata->pkeys() as $k)
						$criteria[] = sprintf('%s,EQ,%s', $k, $Entity->$k);
					$criteriaExp = implode('|',$criteria);
					$response = QueryRunner::fetchOne($this->pdo, $this->Metadata, $this->class, null, null, $criteriaExp, $fetchMode, $fetchSubset);
				} else $response = true;
				$this->Context->trigger(OrmEvent::EVENT_POST_INSERT, null, null, $OrmEvent);
				return $response;
			} else return false;
		} catch(\PDOException $Ex) {
			throw new Exception(100, $this->_oid, $method, $Ex->getCode(), $Ex->getMessage());
		}
	}

	/**
	 * Update Entity using a Query.
	 * @param string $method fetch method (used for trace)
	 * @param object $Entity Entity
	 * @param array $data new Entity data
	 * @param bool $validate
	 * @param int $fetchMode fetch mode (FETCH_OBJ, FETCH_ARRAY, FETCH_RAW), FALSE to skip fetch after insert
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @throws Exception
	 * @return mixed $Entity object or array if $fetchMode, TRUE if not $fetchMode, FALSE on failure
	 */
	protected function execUpdateOne($method, $Entity, $data, $validate=true, $fetchMode=self::FETCH_OBJ, $fetchSubset=null) {
		$OrmEvent = (new OrmEvent($this))->setEntity($Entity);
		try {
			DataMapper::array2object($Entity, $data, $this->Metadata);
			$this->Context->trigger(OrmEvent::EVENT_PRE_UPDATE, null, null, $OrmEvent);
			// detect changes
			$criteria = [];
			foreach($this->Metadata->pkeys() as $k)
				$criteria[] = sprintf('%s,EQ,%s', $k, $Entity->$k);
			$dbData = QueryRunner::fetchOne($this->pdo, $this->Metadata, $this->class, 0, null, implode('|',$criteria), self::FETCH_ARRAY);
			$newData = DataMapper::object2sql($Entity, $this->Metadata);
			$changes = [];
			$props = $this->Metadata->properties();
			foreach($newData as $k=>$v) {
				if($dbData[$k] != $v && !isset($props[$k]['primarykey']) && !$props[$k]['readonly'])
					$changes[$k] = $newData[$k];
			}
			if(!count($changes)) {
				TRACE and $this->trace(LOG_DEBUG, 1, __FUNCTION__, 'SKIP update, Entity not modified');
				return true;
			}
			// onSAve callback
			$this->_onSave->invoke($Entity);
			// re-check changes after onSave()
			$newData = DataMapper::object2sql($Entity, $this->Metadata);
			$changes = [];
			$props = $this->Metadata->properties();
			foreach($newData as $k=>$v) {
				if($dbData[$k] != $v && !isset($props[$k]['primarykey']) && !$props[$k]['readonly'])
					$changes[$k] = $newData[$k];
			}
			// validate
			if($validate) {
				$this->errors = Validator::validate($Entity);
				if(!empty($this->errors)) return false;
			}
			if(QueryRunner::update($this->pdo, $this->Metadata, $Entity, $changes)) {
				if($fetchMode) {
					foreach($this->Metadata->pkeys() as $k)
						$criteria[] = sprintf('%s,EQ,%s', $k, $Entity->$k);
					$criteriaExp = implode('|',$criteria);
					$response = QueryRunner::fetchOne($this->pdo, $this->Metadata, $this->class, null, null, $criteriaExp, $fetchMode, $fetchSubset);
				} else $response = true;
				$this->Context->trigger(OrmEvent::EVENT_POST_UPDATE, null, null, $OrmEvent);
				return $response;
			} else return false;
		} catch(\PDOException $Ex) {
			throw new Exception(300, $this->_oid, $method, $Ex->getCode(), $Ex->getMessage());
		}
	}

	/**
	 * @see ContextAwareInterface
	 */
	function setContext(Context $Context) {
		$this->Context = $Context;
	}
}
Kernel::autoload('metadigit\core\db\orm\util\DataMapper');
Kernel::autoload('metadigit\core\db\orm\util\QueryRunner');
