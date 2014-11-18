<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\util\validator;
use metadigit\core\util\reflection\ReflectionClass;
/**
 * ClassParser
 * Helper class that analyzes Entity class, parsing @validate tags.
 * @internal
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class ClassParser {

	/**
	 * @param string $class Entity class
	 * @return array
	 */
	function parse($class) {
		$metadata = [
			'properties' => [],
			'nullable' => []
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
						$metadata['nullable'][] = $prop;
						unset($value['null']);
					}
					$metadata['properties'][$prop] = array_merge($metadata['properties'][$prop], $value);
				}
			}
		}
		return $metadata;
	}
}
