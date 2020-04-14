<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\db\orm;
use const renovant\core\SYS_CACHE;
use renovant\core\sys;
/**
 * ORM Metadata
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
class Metadata {

	/** SQL metadata: source, target, insertFn, updateFn, deleteFn
	 * @var array */
	protected $sql = [];
	/** Primary keys
	 * @var array */
	protected $pkeys = [];
	/** PK criteriaExp
	 * @var string */
	protected $pkCriteria;
	/** Entity properties definition
	 * @var array*/
	protected $properties = [];
	/** Custom criteriaExp definitions
	 * @var array */
	protected $criteria = [];
	/** Custom orderBy definitions
	 * @var array */
	protected $order = [];
	/** Fetch subsets definition
	 * @var array */
	protected $fetchSubsets = [];
	/** Validate subsets definition
	 * @var array */
	protected $validateSubsets = [];

	function __construct($entityClass) {
		include(__DIR__.'/Metadata.construct.inc');
	}

	function criteria() {
		return $this->criteria;
	}

	function order() {
		return $this->order;
	}

	/** Primary keys
	 * @return array */
	function pkeys() {
		return $this->pkeys;
	}

	/** CriteriaExp based on PKs
	 * @param mixed $EntityOrKeys
	 * @return string criteriaExp
	 */
	function pkCriteria($EntityOrKeys) {
		if(is_object($EntityOrKeys)) {
			$keys = [];
			foreach($this->pkeys as $k) $keys[] = $EntityOrKeys->$k;
		} else $keys = $EntityOrKeys;
		return preg_replace(array_fill(0, count($this->pkeys), '/\?/'), $keys, $this->pkCriteria, 1);
	}

	function properties() {
		return $this->properties;
	}

	function sql($k) {
		return isset($this->sql[$k]) ? $this->sql[$k] : null;
	}

	function fetchSubset($name) {
		if(isset($this->fetchSubsets[$name])) return $this->fetchSubsets[$name];
		trigger_error('Invalid FETCH SUBSET requested: '.$name, E_USER_ERROR);
		return '*';
	}

	function validateSubset($name) {
		if(isset($this->validateSubsets[$name])) return explode(',', $this->validateSubsets[$name]);
		trigger_error('Invalid VALIDATE SUBSET requested: '.$name, E_USER_ERROR);
		return array_keys($this->properties);
	}

	/**
	 * @param string|object $entity class or Entity
	 * @return Metadata
	 */
	static function get($entity) {
		static $cache = [];
		if(is_object($entity)) $entity = get_class($entity);
		if(isset($cache[$entity])) return $cache[$entity];
		$k = $entity.'#ORM-metadata';
		if(!$cache[$entity] = sys::cache(SYS_CACHE)->get($k)) {
			$cache[$entity] = new Metadata($entity);
			sys::cache(SYS_CACHE)->set($k, $cache[$entity], null, 'ORM-metadata');
		}
		return $cache[$entity];
	}
}
