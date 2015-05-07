<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\db\orm;
/**
 * ORM Metadata
 * @author Daniele Sciacchitano <dan@metadigit.it>
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
	protected $subsets = [];

	function __construct($entityClass) {
		include(__DIR__.'/Metadata.construct.inc');
	}

	function criteria() {
		return $this->criteria;
	}

	function order() {
		return $this->order;
	}

	function pkeys() {
		return $this->pkeys;
	}

	function pkCriteria($keys) {
		return preg_replace(array_fill(0, count($this->pkeys), '/\?/'), $keys, $this->pkCriteria, 1);
	}

	function properties() {
		return $this->properties;
	}

	function sql($k) {
		return isset($this->sql[$k]) ? $this->sql[$k] : null;
	}

	function subset() {
		return $this->subset;
	}
}
