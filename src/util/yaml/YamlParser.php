<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\util\yaml;
use metadigit\core\CoreProxy;
/**
 * YAML Parser
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class YamlParser {

	const TYPE_NULL		= 0;
	const TYPE_BOOLEAN	= 1;
	const TYPE_INTEGER	= 2;
	const TYPE_FLOAT	= 3;
	const TYPE_STRING	= 4;
	const TYPE_ARRAY	= 5;
	const TYPE_OBJ		= 6;

	/**
	 * Iterate on YAML, casting properties with PHP boolean, integer, float, array
	 * @param mixed $yamlNode
	 * @return mixed
	 */
	function typeCast($yamlNode) {
		switch (self::parseType($yamlNode)) {
			case self::TYPE_NULL:
				return null; break;
			case self::TYPE_BOOLEAN:
				return (boolean) $yamlNode; break;
			case self::TYPE_INTEGER:
				return (integer) $yamlNode; break;
			case self::TYPE_FLOAT:
				return (float) $yamlNode; break;
			case self::TYPE_ARRAY:
				foreach ($yamlNode as $k => $val) {
					$yamlNode[$k] = $this->typeCast($val);
				}
				return $yamlNode;
			case self::TYPE_OBJ:
				return new CoreProxy(substr($yamlNode, 5));
			default:
				return (string) $yamlNode;
		}
	}

	static function parseType($yamlNode) {
		if(is_null($yamlNode)) return self::TYPE_NULL;
		elseif(is_bool($yamlNode)) return self::TYPE_BOOLEAN;
		elseif(is_int($yamlNode)) return self::TYPE_INTEGER;
		elseif(is_float($yamlNode)) return self::TYPE_FLOAT;
		elseif(is_array($yamlNode)) return self::TYPE_ARRAY;
		elseif(substr($yamlNode, 0, 4) == '!obj') return self::TYPE_OBJ;
		else return self::TYPE_STRING;
	}
}
