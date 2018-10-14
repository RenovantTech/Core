<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */

namespace metadigit\core {
	define('EOL', "\r\n");
	const VERSION = '3.0.0';
	define(__NAMESPACE__.'\DIR', (\Phar::running()) ? \Phar::running() : __DIR__);
}

namespace metadigit\core\http {
	const ENGINE_FILE			= 'FILE';
	const ENGINE_FILE_CSV		= 'FILE_CSV';
	const ENGINE_FILE_EXCEL		= 'FILE_EXCEL';
	const ENGINE_JSON			= 'JSON';
	const ENGINE_PHP			= 'PHP';
	const ENGINE_PHP_TAL		= 'PHP_TAL';
//	const ENGINE_SMARTY			= 'SMARTY';
//	const ENGINE_TWIG			= 'TWIG';
	const ENGINE_X_SEND_FILE	= 'X_SEND_FILE';
}

namespace metadigit\core\trace {
	const T_ERROR		= 0;
	const T_INFO		= 1;
	const T_AUTOLOAD	= 2;
	const T_DB			= 3;
	const T_DEPINJ		= 4;
	const T_CACHE		= 5;
	const T_EVENT		= 6;
}
