<?php
namespace renovant\core;

/**
 * Util functions to parse class or namespace
 */
class sysinfo extends sys {
	public static function namespace(mixed $obj): string {
		list($namespace) = self::parse($obj);
		return $namespace;
	}

	public static function class(mixed $obj): string {
		list(, $class) = self::parse($obj);
		return $class;
	}

	public static function path(mixed $obj): string {
		list(, , $path) = self::parse($obj);
		return $path;
	}

	public static function dir(mixed $obj): string {
		list(, , , $pathDir) = self::parse($obj);
		return $pathDir;
	}

	public static function file(mixed $obj): string {
		list(, , , , $pathFile) = self::parse($obj);
		return $pathFile;
	}

	protected static function parse(string|object $obj): array {
		$obj  = is_object($obj) ? $obj->_ : $obj;
		$path = str_replace('.', '\\', $obj);
		if (false === $i = strrpos($path, '\\')) {
			$namespace = '';
			$class     = $path;
		} else {
			$namespace = substr($path, 0, $i);
			$class     = substr($path, $i + 1);
		}
		$realPath = '';
		foreach (self::$namespaces as $baseName => $baseDir) {
			if (0 === strpos($path, $baseName)) {
				$realPath = $baseDir . str_replace(['\\', '_'], DIRECTORY_SEPARATOR, substr($namespace, strlen($baseName)) . DIRECTORY_SEPARATOR . $class);
				//				$realPath = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $realPath);
				break;
			}
		}
		return [$namespace, $class, $realPath, dirname($realPath), basename($realPath)];
	}
}
