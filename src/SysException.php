<?php
namespace renovant\core;

class SysException extends Exception {
	// bootstrap
	public const ERR21 = 'FATAL ERROR: PUBLIC_DIR not defined in your index.php!';
	public const ERR22 = 'FATAL ERROR: BASE_DIR not defined in your index.php!';
	public const ERR23 = 'FATAL ERROR: DATA_DIR not defined in your index.php!';
	public const ERR24 = 'FATAL ERROR: DATA_DIR "{1}" is NOT writable!';
	public const ERR25 = 'FATAL ERROR: CLI_BOOTSTRAP not defined in your index.php!';
	public const ERR26 = 'FATAL ERROR: CLI_PHP_BIN not defined in your index.php!';
	public const ERR29 = 'FATAL ERROR: please set magic_quotes_gpc Off in your php.ini';

	// constructor
	public const ERR31 = 'Invalid namespace configuration - Namespace "{1}, path "{2}" is NOT a directory';
	public const ERR32 = 'Invalid namespace configuration - Namespace "{1}, path "{2}" is NOT a Phar stream wrapper';

	// configuration
	public const ERR1 = 'Failed to run application module "{1}", path not available.<br>- APP_MOD="{1}" defined in bootstrap file (index.php);<br>- Core XML configuration ({2}) DO NOT contain "{1}" application path.<br>- Default directory "{BASE_DIR}apps/{1}" NOT found.';

	// class autoloading
	public const ERR11 = 'Autoloading class {1}: file {2} not found';
	public const ERR12 = 'Autoloading class {1}: not defined in file {2}';

	// Dispatcher
	public const COD1 = 'Unable to dispatch %s Request: %s:%s%s';
}
