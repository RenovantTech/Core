<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\util\yaml;
/**
 * Yaml Exception
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
class YamlException extends \renovant\core\Exception {
	const COD1 = '%s: YAML config file NOT FOUND in path %s';
	const COD2 = '%s: invalid YAML file: %s';
}
