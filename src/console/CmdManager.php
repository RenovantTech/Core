<?php
namespace renovant\core\console;
use const renovant\core\{CLI_BOOTSTRAP, CLI_PHP_BIN, RUN_DIR};
use const renovant\core\trace\T_INFO;
use renovant\core\sys;
class CmdManager {
	use \renovant\core\CoreTrait;

	static protected $buffer = [];

	function exec(string $cmd) {
		if(PHP_SAPI == 'cli') {
			$exec = CLI_PHP_BIN.' '.CLI_BOOTSTRAP.' '.$cmd;
			sys::trace(LOG_DEBUG, T_INFO, '[EXEC] '.$cmd, $exec, 'sys.CmdManager');
			shell_exec('nohup '.$exec.' > /dev/null 2>&1 & echo $!');
		} else {
			sys::trace(LOG_DEBUG, T_INFO, '[EXEC on shutdown] '.$cmd, null, 'sys.CmdManager');
			self::$buffer[] = $cmd;
		}
	}

	function stop($cmd) {
		$pidLock = RUN_DIR.str_replace(' ', '-', $cmd).'.pid';
		$pid = file_get_contents($pidLock);
		posix_kill($pid, SIGTERM);
	}

	static function shutdown() {
		foreach (self::$buffer as $cmd) {
			$exec = CLI_PHP_BIN.' '.CLI_BOOTSTRAP.' '.$cmd;
			sys::trace(LOG_DEBUG, T_INFO, '[EXEC] '.$cmd, $exec, 'sys.CmdManager::shutdown');
			shell_exec('nohup '.$exec.' > /dev/null 2>&1 & echo $!');
		}
	}
}
if(PHP_SAPI != 'cli')
	register_shutdown_function(__NAMESPACE__.'\CmdManager::shutdown');
