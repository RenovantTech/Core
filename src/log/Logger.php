<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\log;
use const renovant\core\trace\T_INFO;
use renovant\core\sys;
/**
 * Versatile Logger who supports different LogWriters back-ends.
 * The following writers are ready to be used:
 * * LogWriterFile
 * * LogWriterFileTree
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
class Logger extends sys {

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
		self::trace(LOG_DEBUG, T_INFO, null, null, 'sys.Logger->flush');
		foreach(self::$log as $log) {
			list($message, $level, $facility, $time) = $log;
			if(is_null($time)) $time = time();
			foreach($this->levels as $k => $_level) {
				if($level <= $_level && ( is_null($this->facilities[$k]) || $this->facilities[$k] == $facility ))
					$this->writers[$k]->write($time, $message, $level, $facility);
			}
		}
		self::$log = null;
	}
}
