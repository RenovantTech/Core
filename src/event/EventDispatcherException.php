<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\event;
/**
 * EventDispatcherException
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class EventDispatcherException extends \metadigit\core\Exception {
	// runtime
	const COD1 = '';
	const COD4 = '';
	// configuration
	const COD11 = '{1}: XML config file NOT FOUND in path {2}';
	const COD12 = 'EventDispatcher: invalid XML configuration, XSD not validated: {1}';
}