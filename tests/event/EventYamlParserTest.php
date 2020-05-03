<?php
namespace test\event;
use renovant\core\event\EventDispatcherException,
	renovant\core\event\EventYamlParser;

class EventYamlParserTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @throws EventDispatcherException
	 */
	function testParseNamespace() {
		$listeners = EventYamlParser::parseNamespace('test.event');
		$this->assertCount(2, $listeners);
		$this->assertCount(3, $listeners['TEST.EVENT1'][1]);
		$this->assertEquals('substr', $listeners['TEST.EVENT1'][1][0]);
		$this->assertEquals('EventTester::test1', $listeners['TEST.EVENT1'][1][1]);
		$this->assertEquals('LocalMock->foo', $listeners['TEST.EVENT1'][1][2]);
		$this->assertEquals('foo1', $listeners['TEST.EVENT2'][1][0]);
		$this->assertEquals('foo2', $listeners['TEST.EVENT2'][1][1]);
		$this->assertEquals('bar', $listeners['TEST.EVENT2'][2][0]);
	}

	function testParseNamespaceException() {
		try {
			EventYamlParser::parseNamespace('test.xxxx');
			$this->fail('Expected EventDispatcherException not thrown');
		} catch(EventDispatcherException $Ex) {
			$this->assertEquals(11, $Ex->getCode());
			$this->assertMatchesRegularExpression('/YAML config file NOT FOUND/', $Ex->getMessage());
		}
	}
}
