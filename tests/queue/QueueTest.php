<?php
namespace test\queue;
use renovant\core\sys,
	renovant\core\queue\Queue;

class QueueTest extends \PHPUnit\Framework\TestCase {

	static function setUpBeforeClass() {
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS sys_queue;
		');
	}

	static function tearDownAfterClass() {
		sys::pdo('mysql')->exec('
--			DROP TABLE IF EXISTS sys_queue;
		');
	}

	function testConstruct() {
		$Queue = new Queue('mysql');
		$this->assertInstanceOf(Queue::class, $Queue);
		return $Queue;
	}

	/**
	 * @depends testConstruct
	 * @param Queue $Queue
	 */
	function testPush(Queue $Queue) {
		$this->assertEquals(1, $Queue->push(['foo1'=>'bar1'], 10));
		$this->assertEquals(2, $Queue->push(['foo2'=>'bar2'], 10, 'red'));
		$this->assertEquals(3, $Queue->push(['foo3'=>'bar3'], 1));
		$this->assertEquals(4, $Queue->push(['foo4'=>'bar4'], 5));

		$this->assertTrue($Queue->isWaiting(1));
	}

	/**
	 * @depends testConstruct
	 * @param Queue $Queue
	 */
	function testReserve(Queue $Queue) {
		list($id, $job) = $Queue->reserve();
		$this->assertEquals(3, $id);
		$this->assertEquals(['foo3'=>'bar3'], $job);
		$this->assertTrue($Queue->isRunning($id));

		list($id, $job) = $Queue->reserve('red');
		$this->assertEquals(2, $id);
		$this->assertEquals(['foo2'=>'bar2'], $job);
		$this->assertTrue($Queue->isRunning($id));
	}

	/**
	 * @depends testConstruct
	 * @param Queue $Queue
	 */
	function testRelease(Queue $Queue) {
		$this->assertTrue($Queue->release(2));
		$this->assertTrue($Queue->isWaiting(2));
		$this->assertFalse($Queue->isRunning(2));
		$this->assertFalse($Queue->release(2));

		$this->assertTrue($Queue->release(3));
		$this->assertTrue($Queue->isWaiting(3));
		$this->assertFalse($Queue->isRunning(3));
		$this->assertFalse($Queue->release(3));
	}

	/**
	 * @depends testConstruct
	 * @param Queue $Queue
	 */
	function testAck(Queue $Queue) {
		$this->assertFalse($Queue->isDone(3));
		$this->assertFalse($Queue->ack(3));
		list($id, $job) = $Queue->reserve();
		$this->assertEquals(3, $id);
		$this->assertTrue($Queue->ack(3));
		$this->assertTrue($Queue->isDone(3));
	}
}
