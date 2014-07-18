<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\log;
/**
 * General interface for LogWriters used by Logger to store log messages.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
interface LogWriterInterface {

	/**
	 * Write a log entry.
	 * @param int $time log timestamp
	 * @param string $message log message
	 * @param integer $level log level, default: LOG_INFO
	 * @param string $facility optional log facility, default NULL
	 */
	function write($time, $message, $level=LOG_INFO, $facility=null);

}