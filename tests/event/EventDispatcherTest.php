<?php
namespace test\event;
use renovant\core\event\Event,
	renovant\core\event\EventDispatcher,
	renovant\core\event\EventDispatcherException;

$p1 = 'Hello';
$p2 = 'Byebye';
function callback0() {
	global $p1;
	$p1 .= ' Big';
}
function callback1() {
	global $p1;
	$p1 .= ' World 1';
}
function callback2() {
	global $p2;
	$p2 .= ' World 2';
}
function callback3(Event $Event) {
	global $p2;
	$p2 = 'Byebye World 3';
	$Event->stopPropagation();
}
function callback4() {
	global $p2;
	$p2 = 'Byebye World 4';
}
class EventTester {
	static function test1() {
		return 'test1';
	}
}


class EventDispatcherTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$EventDispatcher = new EventDispatcher;
		$this->assertInstanceOf('renovant\core\event\EventDispatcher', $EventDispatcher);
		return $EventDispatcher;
	}

	/**
	 * @depends testConstructor
	 * @param EventDispatcher $EventDispatcher
	 * @throws EventDispatcherException
	 */
	function testInit(EventDispatcher $EventDispatcher) {
		/** @noinspection PhpVoidFunctionResultUsedInspection */
		$this->assertNull($EventDispatcher->init('test.event'));
	}

	/**
	 * @depends testConstructor
	 * @param EventDispatcher $EventDispatcher
	 */
	function testInitException(EventDispatcher $EventDispatcher) {
		try {
			$EventDispatcher->init('test.xxxxxxx');
			$this->fail('Expected EventDispatcherException not thrown');
		} catch(EventDispatcherException $Ex) {
			$this->assertEquals(11, $Ex->getCode());
			$this->assertMatchesRegularExpression('/YAML config file NOT FOUND/', $Ex->getMessage());
		}
	}

	/**
	 * @depends testConstructor
	 * @param EventDispatcher $EventDispatcher
	 */
	function testAddListener(EventDispatcher $EventDispatcher) {
		$EventDispatcher->listen('test.event.add1', 'test\event\callback1');
		$EventDispatcher->listen('test.event.add1', 'test\event\callback2');
		$EventDispatcher->listen('test.event.add1', 'test\event\callback0', 2);
		$EventDispatcher->listen('test.event.add1', 'test\event\callback3');
		$EventDispatcher->listen('test.event.add1', 'test\event\callback4');
		$ReflProp = new \ReflectionProperty('renovant\core\event\EventDispatcher', 'listeners');
		$ReflProp->setAccessible(true);
		$listeners = $ReflProp->getValue($EventDispatcher);
		$this->assertEquals('test\event\callback1', $listeners['TEST.EVENT.ADD1'][1][0]);
		$this->assertEquals('test\event\callback2', $listeners['TEST.EVENT.ADD1'][1][1]);
		$this->assertEquals('test\event\callback3', $listeners['TEST.EVENT.ADD1'][1][2]);
		$this->assertEquals('test\event\callback4', $listeners['TEST.EVENT.ADD1'][1][3]);
		$this->assertEquals('test\event\callback0', $listeners['TEST.EVENT.ADD1'][2][0]);
	}

	/**
	 * @depends testConstructor
	 * @param EventDispatcher $EventDispatcher
	 */
	function testTrigger(EventDispatcher $EventDispatcher) {
		global $p1, $p2;
		$this->assertEquals('Hello', $p1);
		$this->assertEquals('Byebye', $p2);
		$Event = $EventDispatcher->trigger('test.event.add1', ['p1'=>'hello', 'p2'=>'world']);
		$this->assertInstanceOf('renovant\core\event\Event', $Event);
		$this->assertEquals('Hello Big World 1', $p1);
		$this->assertEquals('Byebye World 3', $p2);
		$this->assertTrue($Event->isPropagationStopped());
	}
}
