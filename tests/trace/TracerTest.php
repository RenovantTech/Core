<?php
namespace test\tracer;
use const metadigit\core\trace\T_INFO;
use function metadigit\core\trace;
use metadigit\core\Kernel,
	metadigit\core\trace\Tracer;

class TracerTest extends \PHPUnit_Framework_TestCase {

	function testInit() {
		Tracer::init();
		trace(LOG_DEBUG, T_INFO, 'msg1');
		trace(LOG_ERR, T_INFO, 'err1');

		$trace = Tracer::export();
		$t = array_pop($trace);
		$this->assertEquals(LOG_ERR, $t[2]);
		$this->assertEquals(T_INFO, $t[3]);
		$this->assertEquals('err1', $t[5]);

		$t = array_pop($trace);
		$this->assertEquals(LOG_DEBUG, $t[2]);
		$this->assertEquals(T_INFO, $t[3]);
		$this->assertEquals('msg1', $t[5]);
	}

	function testOnError() {
		file_put_contents(\metadigit\core\LOG_DIR.'system.log', '');
		Tracer::onError(E_USER_NOTICE, 'trigger NOTICE', __FILE__, __LINE__, null);
		Tracer::onError(E_USER_ERROR, 'trigger ERROR', __FILE__, __LINE__, null);
		Kernel::logFlush();
		$lines = file(\metadigit\core\LOG_DIR.'system.log', FILE_IGNORE_NEW_LINES);
		$this->assertStringEndsWith('[ERR] kernel: trigger NOTICE - FILE: '.\metadigit\core\BASE_DIR.'trace/TracerTest.php:29', $lines[1]);
		$this->assertStringEndsWith('[ERR] kernel: trigger ERROR - FILE: '.\metadigit\core\BASE_DIR.'trace/TracerTest.php:30', $lines[2]);
	}

	function testOnException() {
		file_put_contents(\metadigit\core\LOG_DIR.'system.log', '');
		$Ex = new \Exception('test', 123);
		Tracer::onException($Ex);
		Kernel::logFlush();
		$lines = file(\metadigit\core\LOG_DIR.'system.log', FILE_IGNORE_NEW_LINES);
		$this->assertStringEndsWith('[ERR] kernel: Exception[123]: test -  - FILE: '.\metadigit\core\BASE_DIR.'trace/TracerTest.php:39', $lines[3]);
	}
}
