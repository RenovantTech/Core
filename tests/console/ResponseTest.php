<?php
namespace test\console;
use metadigit\core\console\Response;

class ResponseTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$Response = new Response;
		$this->assertInstanceOf(Response::class, $Response);
	}
}
