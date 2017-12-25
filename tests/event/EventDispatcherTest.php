<?php
namespace test\event;
use metadigit\core\event\Event,
	metadigit\core\event\EventDispatcher;

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
		$this->assertInstanceOf('metadigit\core\event\EventDispatcher', $EventDispatcher);
		return $EventDispatcher;
	}

	/**
	 * @depends testConstructor
	 * @param EventDispatcher $EventDispatcher
	 */
	function testAddListener(EventDispatcher $EventDispatcher) {
		$EventDispatcher->listen('test.event1', 'test\event\callback1');
		$EventDispatcher->listen('test.event1', 'test\event\callback2');
		$EventDispatcher->listen('test.event1', 'test\event\callback0', 2);
		$EventDispatcher->listen('test.event1', 'test\event\callback3');
		$EventDispatcher->listen('test.event1', 'test\event\callback4');
		$ReflProp = new \ReflectionProperty('metadigit\core\event\EventDispatcher', 'listeners');
		$ReflProp->setAccessible(true);
		$listeners = $ReflProp->getValue($EventDispatcher);
		$this->assertEquals('test\event\callback1', $listeners['test.event1'][1][0]);
		$this->assertEquals('test\event\callback2', $listeners['test.event1'][1][1]);
		$this->assertEquals('test\event\callback3', $listeners['test.event1'][1][2]);
		$this->assertEquals('test\event\callback4', $listeners['test.event1'][1][3]);
		$this->assertEquals('test\event\callback0', $listeners['test.event1'][2][0]);
	}

	/**
	 * @depends testConstructor
	 * @param EventDispatcher $EventDispatcher
	 */
	function testTrigger(EventDispatcher $EventDispatcher) {
		global $p1, $p2;
		$this->assertEquals('Hello', $p1);
		$this->assertEquals('Byebye', $p2);
		$Event = $EventDispatcher->trigger('test.event1', ['p1'=>'hello', 'p2'=>'world']);
		$this->assertInstanceOf('metadigit\core\event\Event', $Event);
		$this->assertEquals('Hello Big World 1', $p1);
		$this->assertEquals('Byebye World 3', $p2);
		$this->assertTrue($Event->isPropagationStopped());
	}
}
