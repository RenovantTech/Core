<?php
namespace test\debug;
use metadigit\core\Kernel,
	metadigit\core\KernelDebugger;

class KernelDebuggerTest extends \PHPUnit_Framework_TestCase {

	function testError() {
		file_put_contents(\metadigit\core\LOG_DIR.'system.log', '');
		KernelDebugger::onError(E_USER_NOTICE, 'trigger NOTICE', __FILE__, __LINE__, null);
		KernelDebugger::onError(E_USER_ERROR, 'trigger ERROR', __FILE__, __LINE__, null);
		Kernel::logFlush();
		$lines = file(\metadigit\core\LOG_DIR.'system.log', FILE_IGNORE_NEW_LINES);
		$this->assertStringEndsWith('[ERR] kernel: trigger NOTICE - FILE: '.\metadigit\core\BASE_DIR.'KernelDebuggerTest.php:10', $lines[1]);
		$this->assertStringEndsWith('[ERR] kernel: trigger ERROR - FILE: '.\metadigit\core\BASE_DIR.'KernelDebuggerTest.php:11', $lines[2]);
	}

	function testException() {
		file_put_contents(\metadigit\core\LOG_DIR.'system.log', '');
		$Ex = new \Exception('test', 123);
		KernelDebugger::onException($Ex);
		Kernel::logFlush();
		$lines = file(\metadigit\core\LOG_DIR.'system.log', FILE_IGNORE_NEW_LINES);
		$this->assertStringEndsWith('[ERR] kernel: Exception[123]: test -  - FILE: '.\metadigit\core\BASE_DIR.'KernelDebuggerTest.php:20', $lines[3]);
	}
}
