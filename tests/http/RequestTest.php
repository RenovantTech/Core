<?php
namespace test\http;
use metadigit\core\http\Request;

class RequestTest extends \PHPUnit_Framework_TestCase {

	function testConstructor() {
		$_SERVER['REQUEST_URI'] = '/mod1/action2';
		$_GET['id'] = 7;
		$Request = new Request;
		$this->assertEquals(7, $Request->get('id'));

		$Request = new Request(['id'=>8]);
		$this->assertEquals(8, $Request->get('id'));
	}
}