<?php
namespace test\cache;
use metadigit\core\cache\SqliteCache;

class SqliteCacheTest extends \PHPUnit_Framework_TestCase {

	static $SqliteCache;
	static $SqliteCacheWithBuffer;

	static function setUpBeforeClass() {
		self::$SqliteCache = new SqliteCache('sqlite', 'cache');
		self::$SqliteCacheWithBuffer = new SqliteCache('sqlite', 'cache-buffered', true);
	}

	function testConstructor() {
		$this->assertInstanceOf('metadigit\core\cache\SqliteCache', self::$SqliteCache);
		$this->assertInstanceOf('metadigit\core\cache\SqliteCache', self::$SqliteCacheWithBuffer);
	}

	/**
	 * @depends testConstructor
	 */
	function testSet() {
		$this->assertTrue(self::$SqliteCache->set('test1', 'HelloWorld'));
		$this->assertTrue(self::$SqliteCacheWithBuffer->set('test1', 'HelloWorld'));
	}

	/**
	 * @depends testSet
	 */
	function testGet() {
		$this->assertEquals('HelloWorld', self::$SqliteCache->get('test1'));
		$this->assertFalse(self::$SqliteCache->get('test2'));
		$this->assertEquals('HelloWorld', self::$SqliteCacheWithBuffer->get('test1'));
		$this->assertFalse(self::$SqliteCacheWithBuffer->get('test2'));
	}

	/**
	 * @depends testSet
	 */
	function testHas() {
		$this->assertTrue(self::$SqliteCache->has('test1'));
		$this->assertFalse(self::$SqliteCache->has('test2'));
		$this->assertFalse(self::$SqliteCacheWithBuffer->has('test1'));
		$this->assertFalse(self::$SqliteCacheWithBuffer->has('test2'));
	}

	/**
	 * @depends testSet
	 */
	function testDelete() {
		$this->assertTrue(self::$SqliteCache->delete('test1'));
		$this->assertFalse(self::$SqliteCache->has('test1'));
		$this->assertFalse(self::$SqliteCache->get('test1'));

		$this->assertTrue(self::$SqliteCacheWithBuffer->delete('test1'));
		$this->assertFalse(self::$SqliteCacheWithBuffer->has('test1'));
		$this->assertFalse(self::$SqliteCacheWithBuffer->get('test1'));
	}

	/**
	 * @depends testSet
	 */
	function testClean() {
		self::$SqliteCache->set('test1', 'HelloWorld1');
		self::$SqliteCache->set('test2', 'HelloWorld2');
		self::$SqliteCache->set('test3', 'HelloWorld3');
		$this->assertTrue(self::$SqliteCache->clean());
		$this->assertFalse(self::$SqliteCache->has('test1'));
		$this->assertFalse(self::$SqliteCache->has('test2'));
		$this->assertFalse(self::$SqliteCache->has('test3'));
		$this->assertFalse(self::$SqliteCache->get('test1'));
		$this->assertFalse(self::$SqliteCache->get('test2'));
		$this->assertFalse(self::$SqliteCache->get('test3'));

		self::$SqliteCacheWithBuffer->set('test1', 'HelloWorld1');
		self::$SqliteCacheWithBuffer->set('test2', 'HelloWorld2');
		self::$SqliteCacheWithBuffer->set('test3', 'HelloWorld3');
		$this->assertTrue(self::$SqliteCacheWithBuffer->clean());
		$this->assertFalse(self::$SqliteCacheWithBuffer->has('test1'));
		$this->assertFalse(self::$SqliteCacheWithBuffer->has('test2'));
		$this->assertFalse(self::$SqliteCacheWithBuffer->has('test3'));
		$this->assertFalse(self::$SqliteCacheWithBuffer->get('test1'));
		$this->assertFalse(self::$SqliteCacheWithBuffer->get('test2'));
		$this->assertFalse(self::$SqliteCacheWithBuffer->get('test3'));
	}

	/**
	 * @depends testSet
	 */
	function testWriteBuffer() {
		self::$SqliteCacheWithBuffer->set('test1', 'HelloWorld');
		self::$SqliteCacheWithBuffer = null;
		SqliteCache::shutdown();
		self::$SqliteCacheWithBuffer = new SqliteCache('sqlite', 'cache-buffered', true);
		$this->assertEquals('HelloWorld', self::$SqliteCacheWithBuffer->get('test1'));
	}
}
