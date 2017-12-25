<?php
namespace test\event;
use metadigit\core\event\EventDispatcher,
	metadigit\core\event\EventDispatcherException,
	metadigit\core\event\EventYamlParser;

class EventYamlParserTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @throws EventDispatcherException
	 * @throws \metadigit\core\Exception
	 */
	function testParseContext() {
			$EventDispatcher = new EventDispatcher;
			EventYamlParser::parseContext('test.event', $EventDispatcher);
			$RefProp = new \ReflectionProperty('metadigit\core\event\EventDispatcher', 'listeners');
			$RefProp->setAccessible(true);
			$listeners = $RefProp->getValue($EventDispatcher);
			$this->assertCount(2, $listeners);
			$this->assertCount(3, $listeners['test.event1'][1]);
			$this->assertEquals('substr', $listeners['test.event1'][1][0]);
			$this->assertEquals('EventTester::test1', $listeners['test.event1'][1][1]);
			$this->assertEquals('LocalMock->foo', $listeners['test.event1'][1][2]);
			$this->assertEquals('foo1', $listeners['test.event2'][1][0]);
			$this->assertEquals('foo2', $listeners['test.event2'][1][1]);
			$this->assertEquals('bar', $listeners['test.event2'][2][0]);
	}

	/**
	 * @throws \metadigit\core\Exception
	 */
	function testParseContextException() {
		try {
			$EventDispatcher = new EventDispatcher;
			EventYamlParser::parseContext('test.xxxx', $EventDispatcher);
			$this->fail('Expected EventDispatcherException not thrown');
		} catch(EventDispatcherException $Ex) {
			$this->assertEquals(11, $Ex->getCode());
			$this->assertRegExp('/xxxx\/context.yml/', $Ex->getMessage());
		}
	}
}
