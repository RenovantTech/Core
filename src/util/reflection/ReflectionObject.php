<?php
namespace renovant\core\util\reflection;

class ReflectionObject extends \ReflectionObject {

	/**
	 * Set Object property using reflection
	 * @param string $k property name
	 * @param mixed $v property value
	 * @param object $Obj
	 */
	function setProperty(string $k, $v, object $Obj) {
		if($this->hasProperty($k)) {
			$RProperty = $this->getProperty($k);
			$RProperty->setAccessible(true);
			$RProperty->setValue($Obj, $v);
			$RProperty->setAccessible(false);
		}
	}
}
