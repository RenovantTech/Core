<?php
namespace renovant\core\util\yaml;

class YamlException extends \renovant\core\Exception {
	public const COD1 = '%s: YAML config file NOT FOUND in path %s';
	public const COD2 = '%s: invalid YAML file: %s';
}
