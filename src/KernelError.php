<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core;
/**
 * KernelError
 * @internal
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class KernelError {
	// bootstrap
	const ERR21 = 'FATAL ERROR: PUBLIC_DIR not defined in your index.php!';
	const ERR22 = 'FATAL ERROR: BASE_DIR not defined in your index.php!';
	const ERR23 = 'FATAL ERROR: DATA_DIR not defined in your index.php!';
	const ERR24 = 'FATAL ERROR: DATA_DIR "{1}" is NOT writable!';
	const ERR29 = 'FATAL ERROR: please set magic_quotes_gpc Off in your php.ini';

	// constructor
	const ERR31 = 'Invalid namespace configuration - Namespace "{1}, path "{2}" is NOT a directory';
	const ERR32 = 'Invalid namespace configuration - Namespace "{1}, path "{2}" is NOT a Phar stream wrapper';

	// configuration
	const ERR1 = 'Failed to run application "{1}", path not available.<br>- APP="{1}" defined in bootstrap file (index.php);<br>- Core XML configuration ({2}) DO NOT contain "{1}" application path.<br>- Default directory "{BASE_DIR}apps/{1}" NOT found.';

	// class autoloading
	const ERR11 = 'Autoloading class {1}: file {2} not found';
	const ERR12 = 'Autoloading class {1}: not defined in file {2}';
}