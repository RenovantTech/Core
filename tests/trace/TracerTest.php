<?php
namespace test\tracer;

use renovant\core\sys;
use renovant\core\trace\Tracer;

use const renovant\core\trace\{T_ERROR, T_INFO};

class TracerTest extends \PHPUnit\Framework\TestCase {
	public function testInit() {
		sys::trace(LOG_DEBUG, T_INFO, 'msg1');
		sys::trace(LOG_ERR, T_INFO, 'err1');
		$ReflProp = new \ReflectionProperty('renovant\core\sys', 'trace');
		$ReflProp->setAccessible(true);
		$trace = $ReflProp->getValue();

		$t = array_pop($trace);
		$this->assertEquals(LOG_ERR, $t[2]);
		$this->assertEquals(T_INFO, $t[3]);
		$this->assertEquals('err1', $t[5]);

		$t = array_pop($trace);
		$this->assertEquals(LOG_DEBUG, $t[2]);
		$this->assertEquals(T_INFO, $t[3]);
		$this->assertEquals('msg1', $t[5]);
	}

	public function testOnError() {
		trigger_error('NOTICE msg', E_USER_NOTICE);
		trigger_error('ERROR msg', E_USER_ERROR);
		$ReflProp = new \ReflectionProperty('renovant\core\sys', 'trace');
		$ReflProp->setAccessible(true);
		$trace = $ReflProp->getValue();

		array_pop($trace);
		$t = array_pop($trace);
		$this->assertEquals(LOG_ERR, $t[2]);
		$this->assertEquals(T_ERROR, $t[3]);
		$this->assertEquals('E_USER_ERROR', $t[4]);
		$this->assertEquals('ERROR msg', $t[5]);

		array_pop($trace);
		$t = array_pop($trace);
		$this->assertEquals(LOG_ERR, $t[2]);
		$this->assertEquals(T_ERROR, $t[3]);
		$this->assertEquals('E_USER_NOTICE', $t[4]);
		$this->assertEquals('NOTICE msg', $t[5]);
	}

	public function testOnException() {
		$Ex = new \Exception('test', 123);
		Tracer::onException($Ex);
		$ReflProp = new \ReflectionProperty('renovant\core\sys', 'trace');
		$ReflProp->setAccessible(true);
		$trace = $ReflProp->getValue();

		array_pop($trace);
		$t = array_pop($trace);
		$this->assertEquals(LOG_ERR, $t[2]);
		$this->assertEquals(T_ERROR, $t[3]);
		$this->assertEquals('Exception', $t[4]);
		$this->assertEquals('[CODE 123] test', $t[5]);
	}
}
