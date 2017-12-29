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
	 * Get all contexts namespaces
	 * @return array
	 * @throws \metadigit\core\container\ContainerException
	 * @throws \metadigit\core\event\EventDispatcherException
	 * @throws ContextException
	 */
	static function getAllContexts() {
		$namespaces = [];
		// scan global namespaces
		$files = scandir(\metadigit\core\BASE_DIR);
		foreach($files as $file) {
			if(is_file(\metadigit\core\BASE_DIR.$file) && substr($file,-12)=='-context.yml') {
				$namespace = substr($file, 0, -12);
				$namespaces[] = $namespace;
				sys::context()->init($namespace);
			}
		}
		// iterate on namespaces directories
		foreach(self::$namespaces as $namespace => $nsDir) {
			self::scanNamespaceDir($namespace, $nsDir, $namespaces);
		}
		return $namespaces;
	}

	/**
	 * @param $namespace
	 * @param $dir
	 * @param $namespaces
	 * @throws ContextException
	 * @throws \metadigit\core\container\ContainerException
	 * @throws \metadigit\core\event\EventDispatcherException
	 */
	static private function scanNamespaceDir($namespace, $dir, &$namespaces) {
		$files = scandir($dir);
		foreach($files as $file) {
			if(is_file($dir.'/'.$file) && $file=='context.yml') {
				$namespaces[] = $namespace;
				sys::context()->init(str_replace('\\', '.', $namespace));
			} elseif(is_dir($dir.'/'.$file) && !in_array($file, ['.','..'])) {
				self::scanNamespaceDir($namespace.'\\'.$file, $dir.'/'.$file, $namespaces);
			}
		}
	}
}
