<?php
namespace renovant\core;

class SysException extends Exception {
	// bootstrap
	public const ERR21 = 'FATAL ERROR: const PUBLIC_DIR not defined in your boostrap php file';
	public const ERR22 = 'FATAL ERROR: const BASE_DIR not defined in your boostrap php file';
	public const ERR23 = 'FATAL ERROR: const BIN_DIR not defined in your boostrap php file';
	public const ERR24 = 'FATAL ERROR: const DATA_DIR not defined in your boostrap php file';
	public const ERR25 = 'FATAL ERROR: const DATA_DIR "{1}" is NOT writable!';
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
