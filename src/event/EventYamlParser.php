<?php
namespace renovant\core\event;

use renovant\core\sys;
use renovant\core\util\yaml\{Yaml, YamlException};

use const renovant\core\trace\T_DEPINJ;

/**
 * @internal
 */
class EventYamlParser {
	/**
	 * Parse YAML context config
	 * @param string $context
	 * @return array listeners map
	 * @throws EventDispatcherException
	 */
	public static function parse($context): array {
		sys::trace(LOG_DEBUG, T_DEPINJ, $context, null, __METHOD__);
		$listeners = [];
		try {
			$yaml = Yaml::parseContext($context, 'events');
			if (isset($yaml) && is_array($yaml)) {
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
					throw new EventDispatcherException(11, [__METHOD__, $context]);
				case 2:
					throw new EventDispatcherException(12, [__METHOD__, $context]);
			}
		}
		return $listeners;
	}

	/**
	 * Parse YAML config
	 * @param array $yaml YAML config extract
	 * @return array listeners map
	 */
	public static function parseYaml(array $yaml): array {
		$listeners = [];
		foreach ($yaml as $eventName => $eventYAML) {
			$eventName = strtoupper($eventName);
			foreach ($eventYAML as $listenerYAML) {
				if (is_string($listenerYAML)) {
					$listeners[$eventName][1][] = $listenerYAML;
				} elseif (is_array($listenerYAML)) {
					$listeners[$eventName][$listenerYAML['priority']][] = $listenerYAML['listener'];
				}
			}
		}
		return $listeners;
	}
}
