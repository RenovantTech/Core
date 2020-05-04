<?php
namespace test\cache;
use const renovant\core\CACHE_DIR;
use renovant\core\sys,
	renovant\core\cache\OpCache;

class OpCacheTest extends \PHPUnit\Framework\TestCase {

	static function setUpBeforeClass():void {
		sys::pdo('sqlite')->exec('
			DROP TABLE IF EXISTS `opcache1`;
			DROP TABLE IF EXISTS `opcache2`;
		');
	}

	static function tearDownAfterClass():void {
		sys::pdo('sqlite')->exec('
			DROP TABLE IF EXISTS `opcache1`;
			DROP TABLE IF EXISTS `opcache2`;
		');
	}

	function testConstructor() {
		$Cache = new OpCache('cache1', 'sqlite', 'opcache1');
		$this->assertInstanceOf(OpCache::class, $Cache);
		return $Cache;
	}

	function testConstructor2() {
		$CacheWithBuffer = new OpCache('cache2', 'sqlite', 'opcache2', true);
		$this->assertInstanceOf(OpCache::class, $CacheWithBuffer);
		return $CacheWithBuffer;
	}

	function testFile() {
		$RefMethod = new \ReflectionMethod(OpCache::class, '_file');
		$RefMethod->setAccessible(true);
		$id='abcdefghilmnopqrstuvz'; // MD5 = 47a357c2ecb7a46f806fa9b793a74083
		$this->assertEquals(CACHE_DIR.'opc-cache/'.'47a/357/c2ecb7a46f806fa9b793a74083', $RefMethod->invoke(null, 'cache', $id));
	}

	/**
	 * @depends testConstructor
	 * @param OpCache $Cache
	 * @return OpCache
	 */
	function testSet(OpCache $Cache) {
		$RefMethod = new \ReflectionMethod(OpCache::class, '_file');
		$RefMethod->setAccessible(true);

		$this->assertTrue($Cache->set('test1', 'HelloWorld'));
		$this->assertEquals('<?php $expire=0; $data=\'HelloWorld\';',
			file_get_contents($RefMethod->invoke(null, 'cache1', 'test1')));
		$expire = time()-60;
		$this->assertTrue($Cache->set('test2', 'HelloWorld2', $expire));
		$this->assertEquals('<?php $expire='.$expire.'; $data=\'HelloWorld2\';',
			file_get_contents($RefMethod->invoke(null, 'cache1', 'test2')));

		$this->assertTrue($Cache->set('special-chars', 'abcàèìòù#!"£$%&/()=?^\''));
		return $Cache;
	}

	/**
	 * @depends testConstructor2
	 * @param OpCache $CacheWithBuffer
	 * @return OpCache
	 */
	function testSet2(OpCache $CacheWithBuffer) {
		$RefMethod = new \ReflectionMethod(OpCache::class, '_file');
		$RefMethod->setAccessible(true);

		$this->assertTrue($CacheWithBuffer->set('test1', 'HelloWorld'));
		$this->assertFalse(file_exists($RefMethod->invoke(null, 'cache2', 'test1')));
		return $CacheWithBuffer;
	}

	/**
	 * @depends testSet
	 * @param OpCache $Cache
	 */
	function testGet(OpCache $Cache) {
		$this->assertEquals('HelloWorld', $Cache->get('test1'));
		$this->assertEquals('abcàèìòù#!"£$%&/()=?^\'', $Cache->get('special-chars'));
		$this->assertFalse($Cache->get('testX'));
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
	function testGetExpired(OpCache $Cache) {
		$Cache->set('expired', 'ExpiredWorld', time()-60);
		$FreshCache = new OpCache('cache1', 'sqlite', 'opcache1');
		$this->assertFalse($FreshCache->get('expired'));
	}

	/**
	 * @depends testSet
	 * @param OpCache $Cache
	 */
	function testHas(OpCache $Cache) {
		$this->assertTrue($Cache->has('test1'));
		$this->assertFalse($Cache->has('testX'));
	}

	/**
	 * @depends testSet2
	 * @param OpCache $CacheWithBuffer
	 */
	function testHas2(OpCache $CacheWithBuffer) {
		$this->assertTrue($CacheWithBuffer->has('test1'));
		$this->assertFalse($CacheWithBuffer->has('testX'));
	}

	/**
	 * @depends testSet
	 * @param OpCache $Cache
	 */
	function testDelete(OpCache $Cache) {
		$RefMethod = new \ReflectionMethod(OpCache::class, '_file');
		$RefMethod->setAccessible(true);

		$this->assertTrue($Cache->delete('test1'));
		$this->assertFalse($Cache->has('test1'));
		$this->assertFalse($Cache->get('test1'));
		$this->assertFalse(file_exists($RefMethod->invoke(null, 'cache1', 'test1')));
	}

	/**
	 * @depends testSet2
	 * @param OpCache $CacheWithBuffer
	 */
	function testDelete2(OpCache $CacheWithBuffer) {
		$RefMethod = new \ReflectionMethod(OpCache::class, '_file');
		$RefMethod->setAccessible(true);

		$this->assertTrue($CacheWithBuffer->delete('test1'));
		$this->assertFalse($CacheWithBuffer->has('test1'));
		$this->assertFalse($CacheWithBuffer->get('test1'));
		$this->assertFalse(file_exists($RefMethod->invoke(null, 'cache2', 'test1')));
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
		$this->assertEmpty(glob(CACHE_DIR.'opc-cache1/*'));
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
		$CacheWithBuffer = new OpCache('cache2', 'sqlite', 'opcache2', true);
		$this->assertEquals('HelloBuffer', $CacheWithBuffer->get('testbuffer'));
	}
}
