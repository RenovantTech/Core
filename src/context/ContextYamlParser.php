<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\context;
use function metadigit\core\trace;
use metadigit\core\Kernel;
/**
 * ContextParser
 * @internal
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class ContextYamlParser {
	use \metadigit\core\CoreTrait;

	/**
	 * @param string $namespace ContextParser namespace
	 * @param array $includes
	 * @param array $id2classMap
	 * @param array $listeners
	 * @throws ContextException
	 * @internal
	 */
	static function parse($namespace, array &$includes, array &$id2classMap, array &$listeners) {
		$oid = $namespace . '.ContextYamlParser';
		list($namespace2, $className, $dirName, $fileName) = Kernel::parseClassName(str_replace('.', '\\', $namespace . '.Context'));
		if (empty($dirName))
			$yamlPath = \metadigit\core\BASE_DIR . $namespace . '-context.yaml';
		else
			$yamlPath = $dirName . DIRECTORY_SEPARATOR . 'context.yaml';
		TRACE and trace(LOG_DEBUG, TRACE_DEPINJ, '[START] parsing Context YAML', null, $oid);

		if(!file_exists($yamlPath)) throw new ContextException(11, [$oid, $yamlPath]);
		$YAML = yaml_parse_file($yamlPath);
		// @TODO verify YAML content
		// if(!XMLValidator::schema($xmlPath, __DIR__.'/Context.xsd')) throw new ContextException(12, [$xmlPath]);

		// includes
		if(is_array($YAML['includes'])) {
			$includes = (array)$YAML['includes'];
		}

		// verify Context namespaces
		if(is_array($YAML['objects'])) {
			$availableNamespaces = implode(', ', array_merge((array)$namespace, $includes));
			foreach($YAML['objects'] as $id => $objYAML) {
				if(strpos($id, $namespace) !== 0) throw new ContextException(14, [$oid, $id, $namespace]);
				if(isset($objYAML['constructor'])) {
					foreach($objYAML['constructor'] as $arg) {
						if(substr($arg, 0, 4) == '!obj') {
							$id = substr($arg, 5);
							if(strpos($id, $namespace.'.') === 0) continue;
							foreach($includes as $ns) {
								if(strpos($id, $ns.'.') === 0) continue 2;
							}
							throw new ContextException(15, [$oid, '', $id, $availableNamespaces]);
						}

					}
				}
				if(isset($objYAML['properties'])) {
					foreach($objYAML['properties'] as $prop => $propYAML) {
						if(substr($propYAML, 0, 4) == '!obj') {
							$id = substr($propYAML, 5);
							if(strpos($id, $namespace.'.') === 0) continue;
							foreach($includes as $ns) {
								if(strpos($id, $ns.'.') === 0) continue 2;
							}
							throw new ContextException(16, [$oid, $prop, $id, $availableNamespaces]);
						}
					}
				}
			}
		}

		// ID => class MAP
		if(is_array($YAML['objects'])) {
			TRACE and trace(LOG_DEBUG, TRACE_DEFAULT, 'parsing objects', null, $oid);
			$filter = function($v) {
				if(in_array($v, ['\metadigit\core\BaseObject'])) return false;
				if((boolean)strpos($v,'Abstract')) return false;
				if((boolean)strpos('-'.$v,'xi')) return false;
				return true;
			};
			foreach($YAML['objects'] as $id => $objYAML) {
				$parents = array_values(class_parents($objYAML['class']));
				$interfaces = array_values(class_implements($objYAML['class']));
				$all_classes = array_merge([$objYAML['class']] ,$parents, $interfaces);
				$all_classes = array_filter($all_classes, $filter);
				$id2classMap[$id] = $all_classes;
			}
		}

		// events listeners
		if(is_array($YAML['events'])) {
			TRACE and trace(LOG_DEBUG, TRACE_DEFAULT, 'parsing events listeners', null, $oid);
			// parse events in YAML
			foreach($YAML['events'] as $eventName => $eventYAML) {
				foreach($eventYAML as $listener => $listenerYAML) {
					$priority = (isset($listenerYAML['priority'])) ? (int)$listenerYAML['priority'] : 1;
					$listeners[$eventName][$priority][] = $listener;
				}
				krsort($listeners[$eventName], SORT_NUMERIC);
			}
		}
		// scan for EventListenerInterface objects
		if(is_array($YAML['objects'])) {
			foreach($YAML['objects'] as $id => $objYAML) {
				if((new \ReflectionClass($objYAML['class']))->implementsInterface('metadigit\core\event\EventSubscriberInterface')) {
					$events = call_user_func([$objYAML['class'], 'getSubscribedEvents']);
					foreach($events as $eventName => $callbackArray) {
						foreach($callbackArray as $callbackParams) {
							$method = $callbackParams[0];
							$priority = $callbackParams[1];
							$listeners[$eventName][$priority][] = $id.'->'.$method;
						}
					}
				}
			}
		}
		TRACE and trace(LOG_DEBUG, TRACE_DEPINJ, '[END] Context ready', null, $oid);
	}
}
