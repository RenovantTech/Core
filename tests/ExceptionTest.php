<?php
namespace test;
use renovant\core\Exception;

class ExceptionTest extends \PHPUnit\Framework\TestCase {

	function testConstruct() {
		$Ex = new Exception(100, 'Custom message');
		$this->assertInstanceOf('renovant\core\Exception', $Ex);
		$this->assertEquals(100, $Ex->getCode());
		$this->assertEquals('Custom message', $Ex->getMessage());
	}

	function testConstructWithParsedMessage() {
		$Ex = new TestException(100, ['foo', 'bar']);
		$this->assertEquals(100, $Ex->getCode());
		$this->assertEquals('Error foo bar', $Ex->getMessage());

		$Ex = new TestException(101, ['foo', 'bar']);
		$this->assertEquals(101, $Ex->getCode());
		$this->assertEquals('Error (bar):foo', $Ex->getMessage());
	}

	function testGetData() {
		$Ex = new Exception(100, 'Custom message', ['foo', 'bar']);
		$this->assertEquals(['foo', 'bar'], $Ex->getData());
	}
}

class TestException extends Exception {
	const COD100 = 'Error %s %s';
	const COD101 = 'Error (%2$s):%1$s';
}
