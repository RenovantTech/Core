<?php
namespace renovant\core\log\writer;
use const renovant\core\trace\T_INFO;
use renovant\core\sys,
	renovant\core\log\Logger;
class SqliteWriter implements \renovant\core\log\LogWriterInterface {
	use \renovant\core\CoreTrait;

	const SQL_INIT = '
		CREATE TABLE IF NOT EXISTS `%s` (
			date		DATETIME NOT NULL,
			level		TINYINT NOT NULL,
			facility	VARCHAR(50) NULL default NULL,
			message		VARCHAR(255) NOT NULL
		);
		CREATE INDEX IF NOT EXISTS i_level ON log(level);
		CREATE INDEX IF NOT EXISTS i_facility ON log(facility);
	';
	const SQL_INSERT = 'INSERT INTO `%s` (date, level, facility, message) VALUES (:date, :level, :facility, :message)';
	/** PDOStatement for INSERT
	 * @var \PDOStatement */
	private $_pdo_insert;
	/** PDO instance ID
	 * @var string */
	protected $pdo;
	/** PDO table name
	 * @var string */
	protected $table;

	/**
	 * @param string $pdo PDO instance ID
	 * @param string $table table name
	 */
	function __construct($pdo, $table='log') {
		$this->pdo = $pdo;
		$this->table = $table;
		sys::trace(LOG_DEBUG, T_INFO, 'initialize log storage [Sqlite]');
		sys::pdo($pdo)->exec(sprintf(self::SQL_INIT, $table));
	}

	/**
	 * {@inheritdoc}
	 */
	function write($time, $message, $level=LOG_INFO, $facility=null) {
		if(is_null($this->_pdo_insert)) $this->_pdo_insert = sys::pdo($this->pdo)->prepare(sprintf(self::SQL_INSERT, $this->table));
		$this->_pdo_insert->execute(['date'=>$time, 'level'=>Logger::LABELS[$level], 'facility'=>$facility, 'message'=>$message]);
	}
}
