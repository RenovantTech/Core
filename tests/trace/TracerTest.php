<?php
namespace test\tracer;
use const metadigit\core\trace\T_INFO;
use function metadigit\core\trace;
use metadigit\core\trace\Tracer;

class TracerTest extends \PHPUnit_Framework_TestCase {

	function testConstruct() {
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
}
