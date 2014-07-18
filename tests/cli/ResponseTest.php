<?php
namespace test\cli;
use metadigit\core\cli\Response;

class ResponseTest extends \PHPUnit_Framework_TestCase {

	function testConstructor() {
		$Response = new Response;
		$this->assertInstanceOf('metadigit\core\cli\Response', $Response);
	}
}