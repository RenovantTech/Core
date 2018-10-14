<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace metadigit\core\console;
use const metadigit\core\{CLI_BOOTSTRAP, CLI_PHP_BIN, RUN_DIR};
use const metadigit\core\trace\T_INFO;
use metadigit\core\sys;
/**
 * CmdManager
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class CmdManager {
	use \metadigit\core\CoreTrait;

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

	function stop($cmd) {
		$pidLock = RUN_DIR.str_replace(' ', '-', $cmd).'.pid';
		$pid = file_get_contents($pidLock);
		posix_kill($pid, SIGTERM);
	}
}
