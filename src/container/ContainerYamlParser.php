<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
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
		$id2classMap = $class2idMap = $services = [];
		try {
			$yaml = Yaml::parseContext($namespace, 'services', [
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
					$services[$id] = self::parseYaml($objYAML);
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
		return ['id2class'=>$id2classMap, 'class2id'=>$class2idMap, 'services'=>$services];
	}


	/**
	 * Parse Object YAML config
	 * @param array $yaml Object YAML
	 * @return array class, constructor args, properties
	 */
	static function parseYaml(array $yaml) {
		$obj = Container::YAML_OBJ_SKELETON;
		// class
		if($yaml['class']) $obj['class'] = $yaml['class'];
		// constructor args
		if(isset($yaml['constructor']) && is_array($yaml['constructor']))
			$obj['constructor'] = Yaml::typeCast($yaml['constructor']);
		// properties
		if(isset($yaml['properties']) && is_array($yaml['properties']))
			$obj['properties'] = Yaml::typeCast($yaml['properties']);
		return $obj;
	}
}
