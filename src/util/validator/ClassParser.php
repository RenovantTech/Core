<?php
namespace renovant\core\util\validator;
use renovant\core\util\reflection\ReflectionClass;
/**
 * ClassParser
 * Helper class that analyzes Entity class, parsing @validate tags.
 * @internal
 */
class ClassParser {

	/**
	 * @param string $class Entity class
	 * @return array
	 * @throws \ReflectionException
	 */
	function parse($class) {
		$metadata = [
			'properties' => [],
			'null' => [],
			'empty' => []
		];
		$ReflClass = new ReflectionClass($class);
		// properties constraints
		foreach($ReflClass->getProperties() as $ReflProperty) {
			$prop = $ReflProperty->getName();
			$DocComment = $ReflProperty->getDocComment();
			if($DocComment->hasTag('validate')) {
				$metadata['properties'][$prop] = [];
				$values = $DocComment->getTagValues('validate');
				foreach($values as $value) {
					if(array_key_exists('null',$value)) {
						$metadata['null'][] = $prop;
						unset($value['null']);
					}
					if(array_key_exists('empty',$value)) {
						$metadata['empty'][] = $prop;
						unset($value['empty']);
					}
					foreach ($value as $k=>$v) {
						switch ($k) {
							case 'enum': $value[$k] = array_map('trim', explode(',', $v)); break;
						}
					}
					$metadata['properties'][$prop] = array_merge($metadata['properties'][$prop], $value);
				}
			}
		}
		return $metadata;
	}
}
