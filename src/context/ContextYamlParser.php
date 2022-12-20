<?php
namespace renovant\core\context;
use const renovant\core\trace\T_DEPINJ;
use renovant\core\sys,
	renovant\core\util\yaml\Yaml,
	renovant\core\util\yaml\YamlException;
/**
 * @internal
 */
class ContextYamlParser {

	/**
	 * Parse YAML namespace config
	 * @param string $namespace
	 * @return array
	 * @throws ContextException
	 */
	static function parseNamespace(string $namespace): array {
		sys::trace(LOG_DEBUG, T_DEPINJ, $namespace, null, __METHOD__);
		$includes = [];
		try {
			$yaml = Yaml::parseContext($namespace);

			// @TODO verify YAML content
			/*
			if(
				!is_array($YAML) ||
				(isset($YAML['includes']) && !is_array($YAML['includes'])) ||
				(isset($YAML['services']) && !is_array($YAML['services'])) ||
				(isset($YAML['events']) && !is_array($YAML['events']))
			) throw new ContextException(12, [$yamlPath]);
			*/

			// includes
			if(isset($yaml['includes']) && is_array($yaml['includes']))
				$includes = $yaml['includes'];

			// verify Context namespaces
			if(isset($yaml['services']) && is_array($yaml['services'])) {
				$availableNamespaces = implode(', ', array_merge((array)$namespace, $includes));
				foreach($yaml['services'] as $id => $objYAML) {
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
