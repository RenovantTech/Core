<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\log;
use const metadigit\core\trace\T_INFO;
use metadigit\core\sys;
/**
 * Versatile Logger who supports different LogWriters back-ends.
 * The following writers are ready to be used:
 * * LogWriterFile
 * * LogWriterFileTree
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Logger {
	use \metadigit\core\CoreTrait;

	const LABELS = [
		LOG_DEBUG => 'DEBUG',
		LOG_INFO => 'INFO',
		LOG_NOTICE => 'NOTICE',
		LOG_WARNING => 'WARNING',
		LOG_ERR => 'ERR',
		LOG_CRIT => 'CRIT',
		LOG_ALERT => 'ALERT',
		LOG_EMERG => 'EMERG'
	];
	/** Log buffer
	 * @var array */
	protected $buffer = [];
	/** attached LogWriters instances
	 * @var array */
	protected $writers = [];
	/** LogWriters filtering levels
	 * @var array */
	protected $levels = [];
	/** LogWriters filtering facilities
	 * @var array */
	protected $facilities = [];

	function __destruct() {
		$this->flush();
	}

	/**
	 * Add a LogWriter
	 * @param LogWriterInterface $LogWriter
	 * @param int $level the minimum logging level at which the LogWriter will be triggered, default to LOG_INFO
	 * @param string $facility logging facility at which the LogWriter will be triggered, default NULL
	 */
	function addWriter(LogWriterInterface $LogWriter, $level=LOG_INFO, $facility=null) {
		$this->writers[] = $LogWriter;
		$this->levels[] = $level;
		$this->facilities[] = $facility;
	}

	/**
	 * Flush log buffer
	 */
	function flush() {
		foreach($this->buffer as $log) {
			list($message, $level, $facility, $time) = $log;
			if(is_null($time)) $time = time();
			foreach($this->levels as $k => $_level) {
				if($level <= $_level && ( is_null($this->facilities[$k]) || $this->facilities[$k] == $facility ))
					$this->writers[$k]->write($time, $message, $level, $facility);
			}
		}
	}

	/**
	 * Log entry
	 * @param string $message log message
	 * @param integer $level log level, one of the LOG_* constants, default: LOG_INFO
	 * @param string $facility optional log facility, default NULL
	 */
	function log($message, $level=LOG_INFO, $facility=null) {
		sys::trace(LOG_DEBUG, T_INFO, sprintf('[%s] %s: %s', self::LABELS[$level], $facility, $message), null, __METHOD__);
		$this->buffer[] = [$message, $level, $facility, time()];
	}
}
