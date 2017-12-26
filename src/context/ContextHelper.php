<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\context;
use metadigit\core\sys;
/**
 * ContextHelper
 * @internal
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class ContextHelper extends sys {

	/**
	 * @return array[Context]
	 * @throws \metadigit\core\container\ContainerException
	 * @throws \metadigit\core\event\EventDispatcherException
	 */
	static function getAllContexts() {
		$contexts = [];
		// scan global namespaces
		$files = scandir(\metadigit\core\BASE_DIR);
		foreach($files as $file) {
			if(is_file(\metadigit\core\BASE_DIR.$file) && substr($file,-12)=='-context.yml') {
				$contexts[] = sys::context()->init(substr($file, 0, -12));
			}
		}
		// iterate on namespaces directories
		foreach(self::$namespaces as $namespace => $nsDir) {
			self::scanNamespaceDir($namespace, $nsDir, $contexts);
		}
		return $contexts;
	}

	static private function scanNamespaceDir($namespace, $dir, &$contexts) {
		$files = scandir($dir);
		foreach($files as $file) {
			if(is_file($dir.'/'.$file) && $file=='context.yml') {
				$contexts[] = sys::context()->init(str_replace('\\', '.', $namespace));
			} elseif(is_dir($dir.'/'.$file) && !in_array($file, ['.','..'])) {
				self::scanNamespaceDir($namespace.'\\'.$file, $dir.'/'.$file, $contexts);
			}
		}
	}
}
