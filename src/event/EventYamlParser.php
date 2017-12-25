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
	metadigit\core\Exception;
/**
 * Event YAML Parser
 * @author Daniele Sciacchitano <dan@metadigit.it>
 * @internal
 */
class EventYamlParser {

	/**
	 * Parse Context YAML
	 * @param string $namespace Context namespace
	 * @param EventDispatcher $EventDispatcher to attach listeners to
	 * @throws EventDispatcherException
	 * @throws Exception
	 */
	static function parseContext($namespace, EventDispatcher $EventDispatcher) {
		$dirName = sys::info($namespace.'.Context', sys::INFO_PATH_DIR);
		if (empty($dirName))
			$yamlPath = \metadigit\core\BASE_DIR . $namespace . '-context.yml';
		else
			$yamlPath = $dirName . DIRECTORY_SEPARATOR . 'context.yml';
		sys::trace(LOG_DEBUG, T_DEPINJ, 'context: '.$namespace, null, __METHOD__);
		if(!file_exists($yamlPath)) throw new EventDispatcherException(11, [__METHOD__, $yamlPath]);
		$yaml = sys::yaml($yamlPath, 'events');
		// @TODO verify YAML content
//		if(
//			!is_array($YAML) ||
//			(isset($YAML['events']) && !is_array($YAML['events']))
//		) throw new EventDispatcherException(12, [__METHOD__, $yamlPath]);
		self::parseYaml($yaml, $EventDispatcher);
	}

	/**
	 * Parse YAML config
	 * @param array $yaml YAML config extract
	 * @param EventDispatcher $EventDispatcher to attach listeners to
	 */
	static function parseYaml(array $yaml, EventDispatcher $EventDispatcher) {
		sys::trace(LOG_DEBUG, T_INFO, 'parsing YAML events listeners', $yaml, __METHOD__);
		foreach($yaml as $eventName => $eventYAML) {
			foreach ($eventYAML as $listenerYAML) {
				if(is_string($listenerYAML)) {
					$EventDispatcher->listen($eventName, $listenerYAML);
				} elseif (is_array($listenerYAML)) {
					$EventDispatcher->listen($eventName, $listenerYAML['listener'], $listenerYAML['priority']);
				}
			}
		}
	}
}
