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
	metadigit\core\util\yaml\Yaml,
	metadigit\core\util\yaml\YamlException;
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
	 */
	static function parseNamespace($namespace) {
		sys::trace(LOG_DEBUG, T_DEPINJ, $namespace, null, __METHOD__);
		$id2classMap = $class2idMap = [];
		try {
			$yaml = Yaml::parseContext($namespace, 'objects', [
				'!obj' => function($value) {
					return '!obj '.$value;
				}
			]);
			if(isset($yaml) && is_array($yaml)) {
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
			}
		} catch (YamlException $Ex) {
			switch ($Ex->getCode()) {
				case 1:
					throw new ContainerException(11, [__METHOD__, $namespace]); break;
				case 2:
					throw new ContainerException(12, [__METHOD__, $namespace]); break;
			}
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
		$namespace = substr($id, 0, strrpos($id, '.'));
		$yaml = Yaml::parseContext($namespace, 'objects', [
			'!obj' => function($value) {
				return '!obj '.$value;
			}
		]);

		// @TODO verify YAML content
		if(!is_array($yaml[$id])) throw new ContainerException(1, [__METHOD__, $id]);

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
