<?php
namespace test\tracer;
use const metadigit\core\trace\{T_ERROR, T_INFO};
use function metadigit\core\trace;
use metadigit\core\trace\Tracer;

class TracerTest extends \PHPUnit\Framework\TestCase {

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
		trigger_error('NOTICE msg', E_USER_NOTICE);
		trigger_error('ERROR msg', E_USER_ERROR);
		$trace = Tracer::export();

		$t = array_pop($trace);
		$this->assertEquals(LOG_ERR, $t[2]);
		$this->assertEquals(T_ERROR, $t[3]);
		$this->assertEquals('E_USER_ERROR', $t[4]);
		$this->assertEquals('ERROR msg', $t[5]);

		$t = array_pop($trace);
		$this->assertEquals(LOG_ERR, $t[2]);
		$this->assertEquals(T_ERROR, $t[3]);
		$this->assertEquals('E_USER_NOTICE', $t[4]);
		$this->assertEquals('NOTICE msg', $t[5]);
	}

	function testOnException() {
		$Ex = new \Exception('test', 123);
		Tracer::onException($Ex);
		$trace = Tracer::export();
		$t = array_pop($trace);
		$this->assertEquals(LOG_ERR, $t[2]);
		$this->assertEquals(T_ERROR, $t[3]);
		$this->assertEquals('Exception', $t[4]);
		$this->assertEquals('[CODE 123] test', $t[5]);
	}
}
