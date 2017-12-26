<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\event;
use const metadigit\core\trace\{T_INFO, T_DEPINJ};
use metadigit\core\sys,
	metadigit\core\util\yaml\Yaml,
	metadigit\core\util\yaml\YamlException;
/**
 * Event YAML Parser
 * @author Daniele Sciacchitano <dan@metadigit.it>
 * @internal
 */
class EventYamlParser {

	/**
	 * Parse YAML namespace config
	 * @param string $namespace
	 * @return array listeners map
	 * @throws EventDispatcherException
	 */
	static function parseNamespace($namespace) {
		sys::trace(LOG_DEBUG, T_DEPINJ, 'parsing YAML for namespace '.$namespace, null, __METHOD__);
		try {
			$yaml = Yaml::parseContext($namespace, 'events');
			/* @TODO verify YAML content
			if(
				!is_array($YAML) ||
				(isset($YAML['events']) && !is_array($YAML['events']))
			) throw new EventDispatcherException(12, [__METHOD__, $yamlPath]);
			*/
			return self::parseYaml($yaml);
		} catch (YamlException $Ex) {
			switch ($Ex->getCode()) {
				case 1:
					throw new EventDispatcherException(11, [__METHOD__, $namespace]); break;
				case 2:
					throw new EventDispatcherException(12, [__METHOD__, $namespace]); break;
			}
		}
	}

	/**
	 * Parse YAML config
	 * @param array $yaml YAML config extract
	 * @return array listeners map
	 */
	static function parseYaml(array $yaml) {
		sys::trace(LOG_DEBUG, T_INFO, 'parsing YAML events listeners', $yaml, __METHOD__);
		$listeners = [];
		foreach($yaml as $eventName => $eventYAML) {

			foreach ($eventYAML as $listenerYAML) {
				if(is_string($listenerYAML)) {
					$listeners[$eventName][1][] = $listenerYAML;
				} elseif (is_array($listenerYAML)) {
					$listeners[$eventName][$listenerYAML['priority']][] = $listenerYAML['listener'];
				}
			}
		}
		return $listeners;
	}
}
