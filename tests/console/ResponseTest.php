<?php
namespace test\console;
use const renovant\core\TMP_DIR;
use renovant\core\console\Exception,
	renovant\core\console\Response;

class ResponseTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$Response = new Response;
		$this->assertInstanceOf(Response::class, $Response);
		return $Response;
	}

	/**
	 * @depends testConstructor
	 * @param Response $Response
	 */
	function testSetOutputException(Response $Response) {
		try {
			$output = fopen(TMP_DIR.'console.response.out', 'r');
			$Response->setOutput($output);
			$this->fail('Expected Exception not thrown');
		} catch(Exception $Ex) {
			$this->assertEquals(31, $Ex->getCode());
		}
	}

	/**
	 * @depends testConstructor
	 * @param Response $Response
	 * @return Response
	 * @throws \renovant\core\console\Exception
	 */
	function testSetOutput(Response $Response) {
		$output = fopen(TMP_DIR.'console.response.out', 'w');
		$this->assertNull($Response->setOutput($output));
		return $Response;
	}

	/**
	 * @depends testSetOutput
	 * @param Response $Response
	 */
	function testWrite(Response $Response) {
		$Response->write('foo bar');
		$this->assertEquals('foo bar', file_get_contents(TMP_DIR.'console.response.out'));
	}
}
