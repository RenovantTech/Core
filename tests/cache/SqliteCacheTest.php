<?php
namespace test\cache;
use renovant\core\cache\SqliteCache;
use const renovant\core\CACHE_DIR;

class SqliteCacheTest extends \PHPUnit\Framework\TestCase {

	static function setUpBeforeClass() {
		unlink(CACHE_DIR.'sqlite1.sqlite');
		unlink(CACHE_DIR.'sqlite2.sqlite');
	}

	static function tearDownAfterClass() {
		SqliteCache::shutdown();
//		sys::pdo('sqlite')->exec('
//--			DROP TABLE IF EXISTS `sqlite-cache`;
//--			DROP TABLE IF EXISTS `sqlite-cache-buffered`;
//		');
	}

	function testConstructor() {
		$Cache = new SqliteCache('sqlite1', 'cache');
		$this->assertInstanceOf('renovant\core\cache\SqliteCache', $Cache);
		return $Cache;
	}

	function testConstructor2() {
		$CacheWithBuffer = new SqliteCache('sqlite2', 'cache', true);
		$this->assertInstanceOf('renovant\core\cache\SqliteCache', $CacheWithBuffer);
		return $CacheWithBuffer;
	}

	/**
	 * @depends testConstructor
	 * @param SqliteCache $Cache
	 * @return SqliteCache
	 */
	function testSet(SqliteCache $Cache) {
		$this->assertTrue($Cache->set('test1', 'HelloWorld'));
		return $Cache;
	}

	/**
	 * @depends testConstructor2
	 * @param SqliteCache $CacheWithBuffer
	 * @return SqliteCache
	 */
	function testSet2(SqliteCache $CacheWithBuffer) {
		$this->assertTrue($CacheWithBuffer->set('test1', 'HelloWorld'));
		return $CacheWithBuffer;
	}

	/**
	 * @depends testSet
	 * @param SqliteCache $Cache
	 */
	function testGet(SqliteCache $Cache) {
		$this->assertEquals('HelloWorld', $Cache->get('test1'));
		$this->assertFalse($Cache->get('test2'));
	}

	/**
	 * @depends testSet2
	 * @param SqliteCache $CacheWithBuffer
	 */
	function testGet2(SqliteCache $CacheWithBuffer) {
		$this->assertEquals('HelloWorld', $CacheWithBuffer->get('test1'));
		$this->assertFalse($CacheWithBuffer->get('test2'));
	}

	/**
	 * @depends testSet
	 * @param SqliteCache $Cache
	 */
	function testHas(SqliteCache $Cache) {
		$this->assertTrue($Cache->has('test1'));
		$this->assertFalse($Cache->has('test2'));
	}

	/**
	 * @depends testSet2
	 * @param SqliteCache $CacheWithBuffer
	 */
	function testHas2(SqliteCache $CacheWithBuffer) {
		$this->assertTrue($CacheWithBuffer->has('test1'));
		$this->assertFalse($CacheWithBuffer->has('test2'));
	}

	/**
	 * @depends testSet
	 * @param SqliteCache $Cache
	 */
	function testDelete(SqliteCache $Cache) {
		$this->assertTrue($Cache->delete('test1'));
		$this->assertFalse($Cache->has('test1'));
		$this->assertFalse($Cache->get('test1'));
	}

	/**
	 * @depends testSet2
	 * @param SqliteCache $CacheWithBuffer
	 */
	function testDelete2(SqliteCache $CacheWithBuffer) {
		$this->assertTrue($CacheWithBuffer->delete('test1'));
		$this->assertFalse($CacheWithBuffer->has('test1'));
		$this->assertFalse($CacheWithBuffer->get('test1'));
	}

	/**
	 * @depends testSet
	 * @param SqliteCache $Cache
	 */
	function testClean(SqliteCache $Cache) {
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

	/**
	 * @depends testSet2
	 * @param SqliteCache $CacheWithBuffer
	 */
	function testClean2(SqliteCache $CacheWithBuffer) {
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
	 * @param SqliteCache $CacheWithBuffer
	 */
	function testWriteBuffer(SqliteCache $CacheWithBuffer) {
		$CacheWithBuffer->set('test1', 'HelloWorld');
		$CacheWithBuffer = null;
		SqliteCache::shutdown();
		$CacheWithBuffer = new SqliteCache('sqlite2', 'cache', true);
		$this->assertEquals('HelloWorld', $CacheWithBuffer->get('test1'));
	}
}
