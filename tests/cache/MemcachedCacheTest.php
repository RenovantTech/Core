<?php
namespace test\cache;
use renovant\core\cache\MemcachedCache;

class MemcachedCacheTest extends \PHPUnit\Framework\TestCase {

	static function setUpBeforeClass() {

	}

	static function tearDownAfterClass() {
		MemcachedCache::shutdown();
		$Memcached = new \Memcached();
		$Memcached->addServer('localhost',11211,0);
		$Memcached->flush();
	}

	function testConstructor() {
		$Cache = new MemcachedCache('main', ['localhost',11211,0]);
		$this->assertInstanceOf('renovant\core\cache\MemcachedCache', $Cache);
		return $Cache;
	}

	/**
	 * @depends testConstructor
	 * @param MemcachedCache $Cache
	 * @return MemcachedCache
	 */
	function testSet(MemcachedCache $Cache) {
		$this->assertTrue($Cache->set('test1', 'HelloWorld'));
		return $Cache;
	}

	/**
	 * @depends testSet
	 * @param MemcachedCache $Cache
	 */
	function testGet(MemcachedCache $Cache) {
		$this->assertEquals('HelloWorld', $Cache->get('test1'));
		$this->assertFalse($Cache->get('test2'));
	}

	/**
	 * @depends testSet
	 * @param MemcachedCache $Cache
	 */
	function testHas(MemcachedCache $Cache) {
		$this->assertTrue($Cache->has('test1'));
		$this->assertFalse($Cache->has('test2'));
	}

	/**
	 * @depends testSet
	 * @param MemcachedCache $Cache
	 */
	function testDelete(MemcachedCache $Cache) {
		$this->assertTrue($Cache->delete('test1'));
		$this->assertFalse($Cache->has('test1'));
		$this->assertFalse($Cache->get('test1'));
	}

	/**
	 * @depends testSet
	 * @param MemcachedCache $Cache
	 */
	function testClean(MemcachedCache $Cache) {
		$Cache->set('test1', 'HelloWorld1');
		$Cache->set('test2', 'HelloWorld2');
		$Cache->set('test3', 'HelloWorld3');
		$this->assertTrue($Cache->clean());
		$this->assertFalse($Cache->has('test1'));
		$this->assertFalse($Cache->has('test2'));
		$this->assertFalse($Cache->has('test3'));
		$this->assertFalse($Cache->get('test1'));
		$this->assertFalse($Cache->get('test2'));
		$this->assertFalse($Cache->get('test3'));
	}

	function testConstructor2() {
		$CacheWithBuffer = new MemcachedCache('main2', ['localhost',11211,0], true);
		$this->assertInstanceOf('renovant\core\cache\MemcachedCache', $CacheWithBuffer);
		return $CacheWithBuffer;
	}

	/**
	 * @depends testConstructor2
	 * @param MemcachedCache $CacheWithBuffer
	 * @return MemcachedCache
	 */
	function testSet2(MemcachedCache $CacheWithBuffer) {
		$this->assertTrue($CacheWithBuffer->set('test1', 'HelloWorld'));
		return $CacheWithBuffer;
	}

	/**
	 * @depends testSet2
	 * @param MemcachedCache $CacheWithBuffer
	 */
	function testGet2(MemcachedCache $CacheWithBuffer) {
		$this->assertEquals('HelloWorld', $CacheWithBuffer->get('test1'));
		$this->assertFalse($CacheWithBuffer->get('test2'));
	}

	/**
	 * @depends testSet2
	 * @param MemcachedCache $CacheWithBuffer
	 */
	function testHas2(MemcachedCache $CacheWithBuffer) {
		$this->assertTrue($CacheWithBuffer->has('test1'));
		$this->assertFalse($CacheWithBuffer->has('test2'));
	}

	/**
	 * @depends testSet2
	 * @param MemcachedCache $CacheWithBuffer
	 */
	function testDelete2(MemcachedCache $CacheWithBuffer) {
		$this->assertTrue($CacheWithBuffer->delete('test1'));
		$this->assertFalse($CacheWithBuffer->has('test1'));
		$this->assertFalse($CacheWithBuffer->get('test1'));
	}

	/**
	 * @depends testSet2
	 * @param MemcachedCache $CacheWithBuffer
	 */
	function testClean2(MemcachedCache $CacheWithBuffer) {
		$CacheWithBuffer->set('test1', 'HelloWorld1');
		$CacheWithBuffer->set('test2', 'HelloWorld2');
		$CacheWithBuffer->set('test3', 'HelloWorld3');
		$this->assertTrue($CacheWithBuffer->clean());
		$this->assertFalse($CacheWithBuffer->has('test1'));
		$this->assertFalse($CacheWithBuffer->has('test2'));
		$this->assertFalse($CacheWithBuffer->has('test3'));
		$this->assertFalse($CacheWithBuffer->get('test1'));
		$this->assertFalse($CacheWithBuffer->get('test2'));
		$this->assertFalse($CacheWithBuffer->get('test3'));
	}

	/**
	 * @depends testSet2
	 * @param MemcachedCache $CacheWithBuffer
	 */
	function testWriteBuffer(MemcachedCache $CacheWithBuffer) {
		$CacheWithBuffer->set('test1', 'HelloWorld');
		$CacheWithBuffer = null;
		MemcachedCache::shutdown();
		$CacheWithBuffer = new MemcachedCache('main2', 'cache', true);
		$this->assertEquals('HelloWorld', $CacheWithBuffer->get('test1'));
	}
}
