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
	 * @throws \Exception
	 */
	function parse(string $class) {
		$metadata = [
			'properties' => [],
			'null' => [],
			'empty' => []
		];
		$RefClass = new ReflectionClass($class);
		// properties constraints
		foreach($RefClass->getProperties() as $RefProperty) {
			$prop = $RefProperty->getName();
			$DocComment = $RefProperty->getDocComment();
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
					$metadata['properties'][$prop] = array_merge($metadata['properties'][$prop], $value);
				}
			}
		}
		return $metadata;
	}
}
