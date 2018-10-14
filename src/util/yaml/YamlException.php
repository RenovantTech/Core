<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
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
