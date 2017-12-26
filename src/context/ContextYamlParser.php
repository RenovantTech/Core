<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\context;
use const metadigit\core\trace\T_DEPINJ;
use metadigit\core\sys,
	metadigit\core\util\yaml\Yaml,
	metadigit\core\util\yaml\YamlException;
/**
 * ContextParser
 * @author Daniele Sciacchitano <dan@metadigit.it>
 * @internal
 */
class ContextYamlParser {

	/**
	 * Parse YAML namespace config
	 * @param string $namespace
	 * @return array
	 * @throws ContextException
	 */
	static function parseNamespace($namespace) {
		sys::trace(LOG_DEBUG, T_DEPINJ, $namespace, null, __METHOD__);
		$includes = [];
		try {
			$yaml = Yaml::parseContext($namespace);

			// @TODO verify YAML content
			/*
			if(
				!is_array($YAML) ||
				(isset($YAML['includes']) && !is_array($YAML['includes'])) ||
				(isset($YAML['objects']) && !is_array($YAML['objects'])) ||
				(isset($YAML['events']) && !is_array($YAML['events']))
			) throw new ContextException(12, [$yamlPath]);
			*/

			// includes
			if(isset($yaml['includes'])) {
				$includes = (array)$yaml['includes'];
			}

			// verify Context namespaces
			if(isset($yaml['objects'])) {
				$availableNamespaces = implode(', ', array_merge((array)$namespace, $includes));
				foreach($yaml['objects'] as $id => $objYAML) {
					if(strpos($id, $namespace) !== 0) throw new ContextException(14, [__METHOD__, $id, $namespace]);
					if(isset($objYAML['constructor'])) {
						foreach($objYAML['constructor'] as $arg) {
							if(is_string($arg) && substr($arg, 0, 4) == '!obj') {
								$id = substr($arg, 5);
								if(strpos($id, $namespace.'.') === 0) continue;
								foreach($includes as $ns) {
									if(strpos($id, $ns.'.') === 0) continue 2;
								}
								throw new ContextException(15, [__METHOD__, '', $id, $availableNamespaces]);
							}
						}
					}
					if(isset($objYAML['properties'])) {
						foreach($objYAML['properties'] as $prop => $propYAML) {
							if(is_string($propYAML) && substr($propYAML, 0, 4) == '!obj') {
								$id = substr($propYAML, 5);
								if(strpos($id, $namespace.'.') === 0) continue;
								foreach($includes as $ns) {
									if(strpos($id, $ns.'.') === 0) continue 2;
								}
								throw new ContextException(16, [__METHOD__, $prop, $id, $availableNamespaces]);
							}
						}
					}
				}
			}
		} catch (YamlException $Ex) {
			switch ($Ex->getCode()) {
				case 1:
					throw new ContextException(11, [__METHOD__, $namespace]); break;
				case 2:
					throw new ContextException(12, [__METHOD__, $namespace]); break;
			}
		}
		return $includes;
	}
}
