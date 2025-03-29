<?php
namespace renovant\core\container;

use renovant\core\sys;
use renovant\core\util\yaml\{Yaml, YamlException};

use const renovant\core\trace\T_DEPINJ;

class ContainerYamlParser {
	/**
	 * Parse YAML context config
	 * @param string $context
	 * @return array id2class and class2id maps
	 * @throws ContainerException
	 */
	public static function parse(string $context): array {
		sys::trace(LOG_DEBUG, T_DEPINJ, $context, null, __METHOD__);
		$id2classMap = $class2idMap = $services = [];
		try {
			$yaml = Yaml::parseContext($context, 'services', [
				'!obj' => function ($value) {
					return '!obj ' . $value;
				}
			]);
			if (isset($yaml) && is_array($yaml)) {
				$filter = function ($v) {
					if ((bool)strpos($v, 'Abstract')) {
						return false;
					}
					return true;
				};
				foreach ($yaml as $id => $objYAML) {
					$parents          = array_values((array)class_parents($objYAML['class']));
					$interfaces       = array_values((array)class_implements($objYAML['class']));
					$all_classes      = array_merge([$objYAML['class']], $parents, $interfaces);
					$all_classes      = array_filter($all_classes, $filter);
					$id2classMap[$id] = $all_classes;
					foreach ($all_classes as $class) {
						$class2idMap[$class][] = $id;
					}
					$services[$id] = self::parseYaml($objYAML);
				}
			}
		} catch (YamlException $Ex) {
			switch ($Ex->getCode()) {
				case 1:
					throw new ContainerException(11, [__METHOD__, $context]);
				case 2:
					throw new ContainerException(12, [__METHOD__, $context]);
			}
		}
		return ['id2class' => $id2classMap, 'class2id' => $class2idMap, 'services' => $services];
	}

	/**
	 * Parse Object YAML config
	 * @param array $yaml Object YAML
	 * @return array class, constructor args, properties
	 */
	public static function parseYaml(array $yaml): array {
		$obj = Container::YAML_OBJ_SKELETON;
		// class
		if ($yaml['class']) {
			$obj['class'] = $yaml['class'];
		}
		// constructor args
		if (isset($yaml['constructor']) && is_array($yaml['constructor'])) {
			$obj['constructor'] = Yaml::typeCast($yaml['constructor']);
		}
		// properties
		if (isset($yaml['properties']) && is_array($yaml['properties'])) {
			$obj['properties'] = Yaml::typeCast($yaml['properties']);
		}
		return $obj;
	}
}
