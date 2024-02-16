<?php
namespace renovant\core\console;

use const renovant\core\{CLI_BOOTSTRAP, CLI_PHP_BIN, RUN_DIR, TMP_DIR};
use const renovant\core\trace\T_INFO;
use renovant\core\sys;

class CmdManager {
	use \renovant\core\CoreTrait;

	const SQL_ON_START = 'UPDATE %s SET runningPID = :pid, runningAt = :runningAt WHERE id = :id';
	const SQL_ON_END = 'UPDATE %s SET runningPID = NULL, lastTime = runningAt, runningAt = NULL, lastStatus = :lastStatus WHERE id = :id';
	const SQL_ON_LOG = 'INSERT INTO %s_logs (id, startedAt, execTime, status, log) VALUES (:id, :startedAt, :execTime, :status, :log)';

	static protected array $buffer = [];

	/** PDO instance ID */
	protected ?string $pdo;
	/** PDO tables prefix */
	protected string $tablePrefix = 'sys_cmd';
	/** Timestamps of running batches */
	protected array $timestamps = [];

	/**
	 * @param string|null $pdo PDO instance ID, default to "master"
	 * @param string|null $tablePrefix
	 */
	function __construct(?string $pdo = null, ?string $tablePrefix = null) {
		$prevTraceFn = sys::traceFn('sys.CmdManager');
		try {
			$this->pdo = $pdo;
			if ($tablePrefix)
				$this->tablePrefix = $tablePrefix;
			sys::trace(LOG_DEBUG, T_INFO, 'initialize SQL storage');
			$PDO = sys::pdo($this->pdo);
			$driver = $PDO->getAttribute(\PDO::ATTR_DRIVER_NAME);
			$PDO->exec(str_replace('%table%', $this->tablePrefix, file_get_contents(__DIR__ . '/sql/init-' . $driver . '.sql')
			));
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	/**
	 * Before event CONSOLE:CONTROLLER
	 * @throws Exception
	 */
	function onStart(Request $Req, Response $Res) {
		$prevTraceFn = sys::traceFn('sys.CmdManager');
		try {
			$cmd = $Req->CMD();
			$outputFile = TMP_DIR . str_replace(' ', '-', $cmd) . '.' . posix_getpid() . '.out';
			$Res->setOutput(fopen($outputFile, 'w'));
			$this->timestamps[$cmd] = time();
			sys::pdo($this->pdo)->prepare(sprintf(self::SQL_ON_START, $this->tablePrefix))->execute(['id' => $cmd, 'pid' => posix_getpid(), 'runningAt' => date('Y-m-d H:i:s')]);
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	/** After event CONSOLE:RESPONSE */
	function onEnd(string $cmd) {
		$this->_onEnd($cmd, 'OK');
	}

	/** After event CONSOLE:EXCEPTION */
	function onException(string $cmd) {
		$this->_onEnd($cmd, 'ERROR');
	}

	/** After event CONSOLE:SIGTERM */
	function onSIGTERM(string $cmd) {
		$this->_onEnd($cmd, 'SIGTERM');
	}

	function exec(string $cmd, bool $waitShutdown = false) {
		if (!$waitShutdown) {
			$exec = CLI_PHP_BIN . ' ' . CLI_BOOTSTRAP . ' ' . $cmd;
			sys::trace(LOG_DEBUG, T_INFO, '[EXEC] ' . $cmd, $exec, 'sys.CmdManager');
			shell_exec('nohup ' . $exec . ' > /dev/null 2>&1 & echo $!');
		} else {
			sys::trace(LOG_DEBUG, T_INFO, '[EXEC on shutdown] ' . $cmd, null, 'sys.CmdManager');
			self::$buffer[] = $cmd;
		}
	}

	/**
	 * @param string $cmd
	 * @return array|false [$output, $exitCode] on SUCCESS, FALSE on FAILURE
	 */
	function execWait(string $cmd) {
		$exec = CLI_PHP_BIN . ' ' . CLI_BOOTSTRAP . ' ' . $cmd;
		sys::trace(LOG_DEBUG, T_INFO, '[EXEC] ' . $cmd, $exec, 'sys.CmdManager');
		if (exec($exec, $output, $exitCode)) {
			return [$output, $exitCode];
		} else
			return false;
	}

	function stop($cmd) {
		$pidLock = RUN_DIR . str_replace(' ', '-', $cmd) . '.pid';
		$pid = file_get_contents($pidLock);
		posix_kill($pid, SIGTERM);
	}

	function scan() {
		include __DIR__ . '/CmdManager.scan.php';
		scan($this->pdo, $this->tablePrefix);
	}

	protected function _onEnd(string $cmd, $status = null) {
		$prevTraceFn = sys::traceFn('sys.CmdManager');
		try {
			$startTime = $this->timestamps[$cmd];
			unset($this->timestamps[$cmd]);
			$execTime = time() - $startTime;
			$outputFile = TMP_DIR . str_replace(' ', '-', $cmd) . '.' . posix_getpid() . '.out';
			sys::pdo($this->pdo)->prepare(sprintf(self::SQL_ON_END, $this->tablePrefix))->execute(['id' => $cmd, 'lastStatus' => $status]);
			if (filesize($outputFile)) {
				$log = bzcompress(file_get_contents($outputFile));
				sys::pdo($this->pdo)->prepare(sprintf(self::SQL_ON_LOG, $this->tablePrefix))->execute(['id' => $cmd, 'startedAt' => date('Y-m-d H:i:s', $startTime), 'execTime' => $execTime, 'status' => $status, 'log' => $log]);
			}
			unlink($outputFile);
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	static function shutdown() {
		foreach (self::$buffer as $cmd) {
			$exec = CLI_PHP_BIN . ' ' . CLI_BOOTSTRAP . ' ' . $cmd;
			sys::trace(LOG_DEBUG, T_INFO, '[EXEC] ' . $cmd, $exec, 'sys.CmdManager::shutdown');
			shell_exec('nohup ' . $exec . ' > /dev/null 2>&1 & echo $!');
		}
	}
}
if (PHP_SAPI != 'cli')
	register_shutdown_function(__NAMESPACE__ . '\CmdManager::shutdown');
