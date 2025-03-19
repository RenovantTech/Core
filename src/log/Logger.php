<?php
namespace renovant\core\log;

use renovant\core\sys;

use const renovant\core\trace\T_INFO;

/**
 * Versatile Logger who supports different LogWriters back-ends.
 * The following writers are ready to be used:
 * * LogWriterFile
 * * LogWriterFileTree
 */
class Logger extends sys {
	public const LABELS = [
		LOG_DEBUG   => 'DEBUG',
		LOG_INFO    => 'INFO',
		LOG_NOTICE  => 'NOTICE',
		LOG_WARNING => 'WARNING',
		LOG_ERR     => 'ERR',
		LOG_CRIT    => 'CRIT',
		LOG_ALERT   => 'ALERT',
		LOG_EMERG   => 'EMERG'
	];
	/** attached LogWriters instances */
	protected array $writers = [];
	/** LogWriters filtering levels */
	protected array $levels = [];
	/** LogWriters filtering facilities */
	protected array $facilities = [];

	public function __destruct() {
		$this->flush();
	}

	/**
	 * Add a LogWriter
	 * @param LogWriterInterface $LogWriter
	 * @param int $level the minimum logging level at which the LogWriter will be triggered, default to LOG_INFO
	 * @param string $facility logging facility at which the LogWriter will be triggered, default NULL
	 */
	public function addWriter(LogWriterInterface $LogWriter, $level = LOG_INFO, $facility = null) {
		$this->writers[]    = $LogWriter;
		$this->levels[]     = $level;
		$this->facilities[] = $facility;
	}

	/**
	 * Flush log buffer
	 */
	public function flush() {
		self::trace(LOG_DEBUG, T_INFO, null, null, 'sys.Logger->flush');
		foreach (self::$log as $log) {
			list($message, $level, $facility, $time) = $log;
			if (is_null($time)) {
				$time = time();
			}
			foreach ($this->levels as $k => $_level) {
				if ($level <= $_level && (is_null($this->facilities[$k]) || $this->facilities[$k] == $facility)) {
					$this->writers[$k]->write($time, $message, $level, $facility);
				}
			}
		}
		self::$log = null;
	}
}
