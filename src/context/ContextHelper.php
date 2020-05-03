<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\context;
use renovant\core\sys;
/**
 * ContextHelper
 * @internal
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
class ContextHelper extends sys {

	/**
	 * Get all contexts namespaces
	 * @return array
	 * @throws \renovant\core\container\ContainerException
	 * @throws \renovant\core\event\EventDispatcherException
	 * @throws ContextException
	 */
	static function getAllContexts() {
		$namespaces = [];
		// scan global namespaces
		$files = scandir(\renovant\core\BASE_DIR);
		foreach($files as $file) {
			if(is_file(\renovant\core\BASE_DIR.$file) && substr($file,-4)=='.yml') {
				$namespace = substr($file, 0, -4);
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
	 * @throws \renovant\core\container\ContainerException
	 * @throws \renovant\core\event\EventDispatcherException
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
