<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core;
/**
 * KernelException
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class KernelException extends Exception {
	/* Dispatcher */
	const COD1 = 'Unable to dispatch {1} Request with URI: {2}';
}