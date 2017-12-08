<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\container;

class ObjBuilder {

	/**
	 * Build an Object using reflection
	 * @param string $id Object ID
	 * @param string $class Object class
	 * @param array $args constructor args
	 * @param array $properties Object properties
	 * @return object
	 * @internal
	 */
	static function build($id, $class, $args, $properties) {
		$RClass = new \ReflectionClass($class);
		$Obj = (empty($args)) ? $RClass->newInstance() : $RClass->newInstanceArgs($args);
		$RObject = new \ReflectionObject($Obj);
		self::setProperty('_oid', $id, $Obj, $RObject);
		foreach ($properties as $k=>$v) {
			self::setProperty($k, $v, $Obj, $RObject);
		}
		return $Obj;
	}

	/**
	 * Set Object property using reflection
	 * @param string $k property name
	 * @param mixed $v property value
	 * @param object $Obj
	 * @param \ReflectionObject $RObject
	 */
	static protected function setProperty($k, $v, $Obj, \ReflectionObject $RObject) {
		if($RObject->hasProperty($k)) {
			$RProperty = $RObject->getProperty($k);
			$RProperty->setAccessible(true);
			$RProperty->setValue($Obj, $v);
			$RProperty->setAccessible(false);
		}
	}
}
