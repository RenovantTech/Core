<?php
namespace renovant\core\log;
/**
 * General interface for LogWriters used by Logger to store log messages.
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
