<?php
namespace test\cache;
use metadigit\core\cache\SqliteCache;

class SqliteCacheTest extends \PHPUnit_Framework_TestCase {

	function testConstructor() {
		$SqliteCache = new SqliteCache('sqlite', 'cache');
		$this->assertInstanceOf('metadigit\core\cache\SqliteCache', $SqliteCache);
		return $SqliteCache;
	}

	/**
	 * @depends testConstructor
	 */
	function testSet(SqliteCache $SqliteCache) {
		$ret = $SqliteCache->set('test1', 'HelloWorld');
		$this->assertTrue($ret);
		return $SqliteCache;
	}

	/**
	 * @depends testSet
	 */
	function testGet(SqliteCache $SqliteCache) {
		$this->assertEquals('HelloWorld', $SqliteCache->get('test1'));
		$this->assertFalse($SqliteCache->get('test2'));
	}

	/**
	 * @depends testSet
	 */
	function testHas(SqliteCache $SqliteCache) {
		$this->assertTrue($SqliteCache->has('test1'));
		$this->assertFalse($SqliteCache->has('test2'));
	}

	/**
	 * @depends testSet
	 */
	function testDelete(SqliteCache $SqliteCache) {
		$this->assertTrue($SqliteCache->delete('test1'));
		$this->assertFalse($SqliteCache->has('test1'));
		$this->assertFalse($SqliteCache->get('test1'));
	}

	/**
	 * @depends testSet
	 */
	function testClean(SqliteCache $SqliteCache) {
		$SqliteCache->set('test1', 'HelloWorld1');
		$SqliteCache->set('test2', 'HelloWorld2');
		$SqliteCache->set('test3', 'HelloWorld3');
		$this->assertTrue($SqliteCache->clean());
		$this->assertFalse($SqliteCache->has('test1'));
		$this->assertFalse($SqliteCache->has('test2'));
		$this->assertFalse($SqliteCache->has('test3'));
		$this->assertFalse($SqliteCache->get('test1'));
		$this->assertFalse($SqliteCache->get('test2'));
		$this->assertFalse($SqliteCache->get('test3'));
	}
}