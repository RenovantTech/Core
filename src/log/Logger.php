<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\log;
/**
 * Versatile Logger who supports different LogWriters backends.
 * The following writers are ready to be used:
 * * LogWriterFile
 * * LogWriterFileTree
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Logger {
	use \metadigit\core\CoreTrait;

	static $labels = [
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
	 * Write a log entry
	 * @param string $message log message
	 * @param integer $level log level, default: LOG_INFO
	 * @param string $facility optional log facility, default NULL
	 * @param int $time override log timestamp, default NULL
	 */
	function log($message, $level=LOG_INFO, $facility=null, $time=null) {
		if(is_null($time)) $time = time();
		foreach($this->levels as $k => $_level) {
			if($level <= $_level && ( is_null($this->facilities[$k]) || $this->facilities[$k] == $facility ))
				$this->writers[$k]->write($time, $message, $level, $facility);
		}
	}

	static function getLevelName($level) {
		return self::$labels[$level];
	}

	static protected $Logger;

	static function kernelLog(array $conf, array $log) {
		if(is_null(self::$Logger)) {
			self::$Logger = new Logger;
			foreach($conf as $k => $v) {
				list($_level, $_facility, $class, $param1, $param2) = explode('|',$v);
				(empty($_facility)) ? $_facility = null : null;
				self::$Logger->addWriter(new $class($param1, $param2), constant($_level), $_facility);
			}
		}
		foreach($log as $l) call_user_func_array([self::$Logger,'log'], $l);
	}
}