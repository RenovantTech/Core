<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\util\yaml;
/**
 * Yaml Exception
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class YamlException extends \metadigit\core\Exception {
	const COD1 = '%s: YAML config file NOT FOUND in path %s';
	const COD2 = '%s: invalid YAML file: %s';
}
