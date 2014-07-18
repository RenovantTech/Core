<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\db\orm;
/**
 * Entity trait class must use to make ORM Repository work.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
trait EntityTrait {

	/**
	 * Entity constructor
	 * @param array $data Entity data
	 */
	function __construct(array $data=[]) {
		foreach($data as $k=>$v) $this->$k = $v;
		$this->onInit();
	}

	function __get($k) {
		return $this->$k;
	}

	function __set($k, $v) {
		$this->$k = $v;
	}

	protected function onInit() {}
	protected function onSave() {}
	protected function onDelete() {}
}