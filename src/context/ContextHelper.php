<?php
namespace renovant\core\context;

use renovant\core\sys;

/**
 * @internal
 */
class ContextHelper extends sys {
	/**
	 * Get all contexts spaces
	 * @return array
	 * @throws \renovant\core\container\ContainerException
	 * @throws \renovant\core\event\EventDispatcherException
	 * @throws ContextException
	 */
	public static function getAllContexts(): array {
		$contexts = [];
		// scan global namespaces
		$files = scandir(\renovant\core\BASE_DIR);
		foreach ($files as $file) {
			if (is_file(\renovant\core\BASE_DIR . $file) && substr($file, -4) == '.yml') {
				$namespace  = substr($file, 0, -4);
				$contexts[] = $namespace;
				//sys::context()->init($namespace);
			}
		}
		// iterate on namespaces directories
		foreach (self::$namespaces as $namespace => $nsDir) {
			self::scanNamespaceDir($namespace, $nsDir, $contexts);
		}
		return $contexts;
	}

	/**
	 * @param $namespace
	 * @param $dir
	 * @param $namespaces
	 * @throws ContextException
	 * @throws \renovant\core\container\ContainerException
	 * @throws \renovant\core\event\EventDispatcherException
	 */
	private static function scanNamespaceDir(string $namespace, string $dir, array &$contexts) {
		$files = scandir($dir);
		foreach ($files as $file) {
			if (is_file($dir . '/' . $file) && $file == 'context.yml') {
				$contexts[] = str_replace('\\', '.', $namespace);
				//sys::context()->init(str_replace('\\', '.', $namespace));
			} elseif (is_dir($dir . '/' . $file) && !in_array($file, ['.', '..'])) {
				self::scanNamespaceDir($namespace . '\\' . $file, $dir . '/' . $file, $contexts);
			}
		}
	}
}
