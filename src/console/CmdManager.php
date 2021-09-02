<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\console;
use const renovant\core\{CLI_BOOTSTRAP, CLI_PHP_BIN, RUN_DIR};
use const renovant\core\trace\T_INFO;
use renovant\core\sys;
/**
 * CmdManager
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
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
			sys::trace(LOG_DEBUG, T_INFO, '[EXEC] '.$cmd, $exec, 'sys.CmdManager');
			shell_exec('nohup '.$exec.' > /dev/null 2>&1 & echo $!');
		}
	}
}
if(PHP_SAPI != 'cli')
	register_shutdown_function(__NAMESPACE__.'\CmdManager::shutdown');
