<?php
namespace test\cli;
use metadigit\core\cli\Request;

class RequestTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$_SERVER['REQUEST_URI'] = '/mod1/action2';
		$_SERVER['argv'][] = 'sys foo';
		$_SERVER['argv'][] = '--id=7';
		$Request = new Request;
		$this->assertInstanceOf('metadigit\core\cli\Request', $Request);
		$this->assertEquals(7, $Request->get('id'));

		$argv[] = 'sys foo';
		$argv[] = '--id=8';
		$Request = new Request($argv);
		$this->assertEquals(8, $Request->get('id'));
	}
}
