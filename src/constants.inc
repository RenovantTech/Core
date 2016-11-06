<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */

namespace metadigit\core;

define('EOL', "\r\n");
const VERSION = '3.0.0';
define(__NAMESPACE__.'\DIR', (\Phar::running()) ? \Phar::running() : __DIR__);


namespace metadigit\core\trace;

const T_ERROR		= 0;
const T_DEFAULT		= 1;
const T_AUTOLOADING	= 2;
const T_DB			= 3;
const T_DEPINJ		= 4;
const T_CACHE		= 5;
const T_EVENT		= 6;
