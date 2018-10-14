<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\db\orm;
use renovant\core\util\Date,
	renovant\core\util\DateTime;
/**
 * Entity trait class must use to make ORM Repository work.
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
trait EntityTrait {

	/**
	 * Entity constructor
	 * @param array $data Entity data
	 */
	function __construct(array $data=[]) {
		$this->__invoke($data);
		$this->onInit();
	}

	function __get($k) {
		return $this->$k;
	}

	function __invoke(array $data=[]) {
		static $prop = null;
		if(!$prop) $prop = Metadata::get($this)->properties();
		foreach($data as $k=>$v) {
			if(!isset($prop[$k])) {
				trigger_error('Undefined ORM metadata for property "'.$k.'", must have tag @orm', E_USER_ERROR);
				continue;
			}
			if($prop[$k]['null'] && (is_null($v) || $v==='')) {
				$this->$k = null;
				continue;
			}
			switch($prop[$k]['type']) {
				case 'string': $this->$k = $v; break;
				case 'integer': $this->$k = (int) $v; break;
				case 'float': $this->$k = (float) $v; break;
				case 'boolean': $this->$k = (bool) $v; break;
				case 'date': $this->$k = ($v instanceof \DateTime) ? $v : new Date($v); break;
				case 'datetime': $this->$k = ($v instanceof \DateTime) ? $v : new DateTime($v); break;
				case 'object': $this->$k = (is_object($v)) ? $v : unserialize($v); break;
				case 'array': $this->$k = (is_array($v)) ? $v : unserialize($v); break;
			}
		}
	}

	function __set($k, $v) {
		$this([$k => $v]);
	}

	protected function onInit() {}
	protected function onSave() {}
	protected function onDelete() {}
}
