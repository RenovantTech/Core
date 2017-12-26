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
	metadigit\core\Exception,
	metadigit\core\util\yaml\Yaml;
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
	 * @throws \metadigit\core\util\yaml\YamlException
	 */
	static function parseNamespace($namespace) {
		$dirName = sys::info($namespace.'.Context', sys::INFO_PATH_DIR);
		if (empty($dirName))
			$yamlPath = \metadigit\core\BASE_DIR . $namespace . '-context.yml';
		else
			$yamlPath = $dirName . DIRECTORY_SEPARATOR . 'context.yml';
		sys::trace(LOG_DEBUG, T_DEPINJ, 'context: '.$namespace, null, __METHOD__);
		if(!file_exists($yamlPath)) throw new EventDispatcherException(11, [__METHOD__, $yamlPath]);
		$yaml = Yaml::parseFile($yamlPath, 'events');
		// @TODO verify YAML content
//		if(
//			!is_array($YAML) ||
//			(isset($YAML['events']) && !is_array($YAML['events']))
//		) throw new EventDispatcherException(12, [__METHOD__, $yamlPath]);
		return self::parseYaml($yaml);
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
