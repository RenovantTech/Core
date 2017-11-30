<?php
namespace test\cache;
use const metadigit\core\CACHE_DIR;
use metadigit\core\cache\OpCache;

class OpCacheTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$Cache = new OpCache('cache1');
		$this->assertInstanceOf('metadigit\core\cache\OpCache', $Cache);
		return $Cache;
	}

	function testConstructor2() {
		$CacheWithBuffer = new OpCache('cache2', true);
		$this->assertInstanceOf('metadigit\core\cache\OpCache', $CacheWithBuffer);
		return $CacheWithBuffer;
	}

	/**
	 * @depends testConstructor
	 * @param OpCache $Cache
	 * @return OpCache
	 */
	function testSet(OpCache $Cache) {
		$this->assertTrue($Cache->set('test1', 'HelloWorld'));
		$this->assertEquals('<?php $data=\'HelloWorld\';', file_get_contents(CACHE_DIR.'opc-cache1/'.substr(chunk_split(md5('test1'),8,'/'),0,-1)));
		return $Cache;
	}

	/**
	 * @depends testConstructor2
	 * @param OpCache $CacheWithBuffer
	 * @return OpCache
	 */
	function testSet2(OpCache $CacheWithBuffer) {
		$this->assertTrue($CacheWithBuffer->set('test1', 'HelloWorld'));
		$this->assertFalse(file_exists(CACHE_DIR.'opc-cache2/'.substr(chunk_split(md5('test1'),8,'/'),0,-1)));
		return $CacheWithBuffer;
	}

	/**
	 * @depends testSet
	 * @param OpCache $Cache
	 */
	function testGet(OpCache $Cache) {
		$this->assertEquals('HelloWorld', $Cache->get('test1'));
		$this->assertFalse($Cache->get('test2'));
	}

	/**
	 * @depends testSet2
	 * @param OpCache $CacheWithBuffer
	 */
	function testGet2(OpCache $CacheWithBuffer) {
		$this->assertEquals('HelloWorld', $CacheWithBuffer->get('test1'));
		$this->assertFalse($CacheWithBuffer->get('test2'));
	}

	/**
	 * @depends testSet
	 * @param OpCache $Cache
	 */
	function testHas(OpCache $Cache) {
		$this->assertTrue($Cache->has('test1'));
		$this->assertFalse($Cache->has('test2'));
	}

	/**
	 * @depends testSet2
	 * @param OpCache $CacheWithBuffer
	 */
	function testHas2(OpCache $CacheWithBuffer) {
		$this->assertTrue($CacheWithBuffer->has('test1'));
		$this->assertFalse($CacheWithBuffer->has('test2'));
	}

	/**
	 * @depends testSet
	 * @param OpCache $Cache
	 */
	function testDelete(OpCache $Cache) {
		$this->assertTrue($Cache->delete('test1'));
		$this->assertFalse($Cache->has('test1'));
		$this->assertFalse($Cache->get('test1'));
		$this->assertFalse(file_exists(CACHE_DIR.'opc-cache1/'.substr(chunk_split(md5('test1'),8,'/'),0,-1)));
	}

	/**
	 * @depends testSet2
	 * @param OpCache $CacheWithBuffer
	 */
	function testDelete2(OpCache $CacheWithBuffer) {
		$this->assertTrue($CacheWithBuffer->delete('test1'));
		$this->assertFalse($CacheWithBuffer->has('test1'));
		$this->assertFalse($CacheWithBuffer->get('test1'));
		$this->assertFalse(file_exists(CACHE_DIR.'opc-cache2/'.substr(chunk_split(md5('test1'),8,'/'),0,-1)));
	}

	/**
	 * @depends testSet
	 * @param OpCache $Cache
	 */
	function testClean(OpCache $Cache) {
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
	 * @param OpCache $CacheWithBuffer
	 */
	function testClean2(OpCache $CacheWithBuffer) {
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
	 * @param OpCache $CacheWithBuffer
	 */
	function testWriteBuffer(OpCache $CacheWithBuffer) {
		$CacheWithBuffer->set('testbuffer', 'HelloBuffer');
		$CacheWithBuffer = null;
		OpCache::shutdown();
		$CacheWithBuffer = new OpCache('cache2', true);
		$this->assertEquals('HelloBuffer', $CacheWithBuffer->get('testbuffer'));
	}
}
