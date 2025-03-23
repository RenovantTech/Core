<?php
namespace renovant\core\context;

use renovant\core\sys;

/**
 * @internal
 */
class ContextHelper extends sys {
	/**
	 * Get all contexts namespaces
	 * @return array
	 * @throws \renovant\core\container\ContainerException
	 * @throws \renovant\core\event\EventDispatcherException
	 * @throws ContextException
	 */
	public static function getAllContexts(): array {
		$ctxNamespaces = [];
		// scan global namespaces
		$files = scandir(\renovant\core\BASE_DIR);
		foreach ($files as $file) {
			if (is_file(\renovant\core\BASE_DIR . $file) && substr($file, -4) == '.yml') {
				$namespace       = substr($file, 0, -4);
				$ctxNamespaces[] = $namespace;
				//sys::context()->init($namespace);
			}
		}
		// iterate on namespaces directories
		foreach (self::$namespaces as $namespace => $nsDir) {
			self::scanNamespaceDir($namespace, $nsDir, $ctxNamespaces);
		}
		return $ctxNamespaces;
	}

	/**
	 * @param $namespace
	 * @param $dir
	 * @param $namespaces
	 * @throws ContextException
	 * @throws \renovant\core\container\ContainerException
	 * @throws \renovant\core\event\EventDispatcherException
	 */
	private static function scanNamespaceDir(string $namespace, string $dir, array &$ctxNamespaces) {
		$files = scandir($dir);
		foreach ($files as $file) {
			if (is_file($dir . '/' . $file) && $file == 'context.yml') {
				$ctxNamespaces[] = str_replace('\\', '.', $namespace);
				//sys::context()->init(str_replace('\\', '.', $namespace));
			} elseif (is_dir($dir . '/' . $file) && !in_array($file, ['.', '..'])) {
				self::scanNamespaceDir($namespace . '\\' . $file, $dir . '/' . $file, $ctxNamespaces);
			}
		}
	}
}
