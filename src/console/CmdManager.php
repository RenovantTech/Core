<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\console;
use const metadigit\core\{CLI_BOOTSTRAP, CLI_PHP_BIN};
use const metadigit\core\trace\T_INFO;
use metadigit\core\sys;
/**
 * CmdManager
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class CmdManager {
	use \metadigit\core\CoreTrait;

	/** PDO instance ID
	 * @var \PDO */
	protected $pdo;
	/** PDO table name
	 * @var string */
	protected $table;
	/** Timestamps of running batches
	 * @var array */
	protected $timestamps = [];

	/**
	 * @param string $pdo PDO instance ID
	 * @param string $table table name
	 */
	function __construct($pdo='system', $table='sys_batches') {
		$this->pdo = $pdo;
		$this->table = $table;
	}

	/**
	 * CLI command start
	 * @param string $cmd
	 * @return integer command process PID
	 */
	function start($cmd) {
		$cmd = CLI_PHP_BIN.' '.CLI_BOOTSTRAP.' '.$cmd;
		sys::trace(LOG_DEBUG, T_INFO, 'START '.$cmd, null, 'sys.CmdManager');
		return (int) shell_exec('nohup '.$cmd.' > /dev/null 2>&1 & echo $!');
	}
}
