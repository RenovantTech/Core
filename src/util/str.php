<?php
namespace renovant\core\util;

class str {

	static function camel2kebab(string $string): string {
		return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $string));
	}

	static function camel2snake(string $string): string {
		return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
	}

	static function kebab2camel(string $string): string {
		return lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $string))));
	}

	static function kebab2snake(string $string): string {
		return str_replace('-', '_', $string);
	}

	static function snake2camel(string $string): string {
		return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $string))));
	}

	static function snake2kebab(string $string): string {
		return str_replace('_', '-', $string);
	}
}
