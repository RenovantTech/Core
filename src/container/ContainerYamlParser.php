<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\container;
use const metadigit\core\trace\T_DEPINJ;
use metadigit\core\sys,
	metadigit\core\util\yaml\Yaml;
/**
 * Dependency Injection ContainerParser
 * @author Daniele Sciacchitano <dan@metadigit.it>
 * @internal
 */
class ContainerYamlParser {

	/**
	 * Parse YAML namespace config
	 * @param $namespace
	 * @return array id2class and class2id maps
	 * @throws ContainerException
	 * @throws \metadigit\core\util\yaml\YamlException
	 */
	static function parseNamespace($namespace) {
		sys::trace(LOG_DEBUG, T_DEPINJ, 'parsing YAML for namespace '.$namespace, null, __METHOD__);
		$dirName = sys::info($namespace.'.Context', sys::INFO_PATH_DIR);
		if (empty($dirName))
			$yamlPath = \metadigit\core\BASE_DIR . sys::info($namespace.'.Context', sys::INFO_NAMESPACE) . '-context.yml';
		else
			$yamlPath = $dirName . DIRECTORY_SEPARATOR . 'context.yml';
		if(!file_exists($yamlPath)) throw new ContainerException(11, [__METHOD__, $yamlPath]);
		$yaml = Yaml::parseFile($yamlPath, 'objects', [
			'!obj' => function($value, $tag, $flags) {
				return '!obj '.$value;
			}
		]);

		$id2classMap = $class2idMap = [];
		$filter = function($v) {
			if((boolean)strpos($v,'Abstract')) return false;
			return true;
		};
		foreach($yaml as $id => $objYAML) {
			$parents = array_values(class_parents($objYAML['class']));
			$interfaces = array_values(class_implements($objYAML['class']));
			$all_classes = array_merge([$objYAML['class']], $parents, $interfaces);
			$all_classes = array_filter($all_classes, $filter);
			$id2classMap[$id] = $all_classes;
			foreach($all_classes as $class)
				$class2idMap[$class][] = $id;
		}
		return [$id2classMap, $class2idMap];
	}


	/**
	 * Parse YAML object config
	 * @param string $id object ID
	 * @return array class, constructor args, properties
	 * @throws ContainerException
	 * @throws \metadigit\core\util\yaml\YamlException
	 */
	static function parseObject($id) {
		sys::trace(LOG_DEBUG, T_DEPINJ, 'parsing YAML for object '.$id, null, __METHOD__);
		$dirName = sys::info($id, sys::INFO_PATH_DIR);
		if (empty($dirName))
			$yamlPath = \metadigit\core\BASE_DIR . sys::info($id, sys::INFO_NAMESPACE) . '-context.yml';
		else
			$yamlPath = $dirName . DIRECTORY_SEPARATOR . 'context.yml';
		if(!file_exists($yamlPath)) throw new ContainerException(11, [__METHOD__, $yamlPath]);
		$yaml = Yaml::parseFile($yamlPath, 'objects', [
			'!obj' => function($value, $tag, $flags) {
				return '!obj '.$value;
			}
		]);

		// @TODO verify YAML content
		if(!is_array($yaml[$id])) throw new ContainerException(1, [__METHOD__, $id, $yamlPath]);

		// class
		$class = $yaml[$id]['class'];
		// constructor args
		$args = [];
		if(isset($yaml[$id]['constructor']) && is_array($yaml[$id]['constructor']))
			$args = Yaml::typeCast($yaml[$id]['constructor']);
		// properties
		$properties = [];
		if(isset($yaml[$id]['properties']) && is_array($yaml[$id]['properties']))
			$properties = Yaml::typeCast($yaml[$id]['properties']);
		return [$class, $args, $properties];
	}
}
