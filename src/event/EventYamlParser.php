<?php
namespace renovant\core\event;
use const renovant\core\trace\T_DEPINJ;
use renovant\core\sys,
	renovant\core\util\yaml\Yaml,
	renovant\core\util\yaml\YamlException;
/**
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
		sys::trace(LOG_DEBUG, T_DEPINJ, $namespace, null, __METHOD__);
		$listeners = [];
		try {
			$yaml = Yaml::parseContext($namespace, 'events');
			if(isset($yaml) && is_array($yaml)) {
				/* @TODO verify YAML content
				if(
				!is_array($YAML) ||
				(isset($YAML['events']) && !is_array($YAML['events']))
				) throw new EventDispatcherException(12, [__METHOD__, $yamlPath]);
				 */
				$listeners = self::parseYaml($yaml);
			}
		} catch (YamlException $Ex) {
			switch ($Ex->getCode()) {
				case 1:
					throw new EventDispatcherException(11, [__METHOD__, $namespace]); break;
				case 2:
					throw new EventDispatcherException(12, [__METHOD__, $namespace]); break;
			}
		}
		return $listeners;
	}

	/**
	 * Parse YAML config
	 * @param array $yaml YAML config extract
	 * @return array listeners map
	 */
	static function parseYaml(array $yaml) {
		$listeners = [];
		foreach($yaml as $eventName => $eventYAML) {
			$eventName = strtoupper($eventName);
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
