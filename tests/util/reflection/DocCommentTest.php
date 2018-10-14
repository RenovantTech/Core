<?php
namespace test\util\reflection;
use renovant\core\util\reflection;

class DocCommentTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$DocComment = (new reflection\ReflectionClass('test\util\reflection\Mock'))->getDocComment();
		$this->assertInstanceOf('renovant\core\util\reflection\DocComment', $DocComment);
	}

	/**
	 * @depends testConstructor
	 */
	function testGetDescription() {
		$DocComment = (new reflection\ReflectionClass('test\util\reflection\Mock'))->getDocComment();
		$this->assertEquals('MockClass used for testing renovant\core\util\reflection\*', $DocComment->getDescription());

		$DocComment = (new reflection\ReflectionProperty('test\util\reflection\Mock', 'var1'))->getDocComment();
		$this->assertEquals("Var 1\n var 1 description", $DocComment->getDescription());

		$DocComment = (new reflection\ReflectionMethod('test\util\reflection\Mock', 'method1'))->getDocComment();
		$this->assertEquals('Test method 1', $DocComment->getDescription());

		$DocComment = (new reflection\ReflectionMethod('test\util\reflection\Mock', 'method2'))->getDocComment();
		$this->assertEquals("Method 2\n method 2 line 2\n\n method 2 line 4", $DocComment->getDescription());
	}

	/**
	 * @depends testConstructor
	 */
	function testGetAllTags() {
		$DocComment = (new reflection\ReflectionMethod('test\util\reflection\Mock', 'functionTag'))->getDocComment();
		$tags = $DocComment->getAllTags();
		$this->assertCount(3, $tags);
		$this->assertArrayHasKey('tag1', $tags);
		$this->assertCount(2, $tags['tag1']);
		$this->assertCount(1, $tags['tag2']);
		$this->assertCount(1, $tags['tag3']);
	}

	/**
	 * @depends testConstructor
	 */
	function testCountTag() {
		$DocComment = (new reflection\ReflectionMethod('test\util\reflection\Mock', 'functionTag'))->getDocComment();
		$this->assertEquals(2, $DocComment->countTag('tag1'));
		$this->assertEquals(1, $DocComment->countTag('tag2'));
		$this->assertEquals(0, $DocComment->countTag('not-exists'));
	}

	/**
	 * @depends testConstructor
	 */
	function testGetTag() {
		$DocComment = (new reflection\ReflectionMethod('test\util\reflection\Mock', 'functionTag'))->getDocComment();
		$this->assertEquals('foo bar', $DocComment->getTag('tag1'));
		$this->assertEquals(['foo'=>13, 'bar'=>289], $DocComment->getTag('tag1', 1));
		$this->assertCount(4, $DocComment->getTag('tag2'));
		$this->assertNull($DocComment->getTag('not-exists'));
	}

	/**
	 * @depends testConstructor
	 */
	function testGetTagValues() {
		$DocComment = (new reflection\ReflectionClass('test\util\reflection\Mock'))->getDocComment();
		$this->assertCount(1, $DocComment->getTagValues('author'));
		$this->assertEquals('Daniele Sciacchitano <dan@renovant.tech>', $DocComment->getTagValues('author')[0]);

		$DocComment = (new reflection\ReflectionMethod('test\util\reflection\Mock', 'method1'))->getDocComment();
		$this->assertCount(2, $DocComment->getTagValues('param'));
		$this->assertEquals('integer $id', $DocComment->getTagValues('param')[0]);

		$DocComment = (new reflection\ReflectionMethod('test\util\reflection\Mock', 'functionTag'))->getDocComment();
		$this->assertCount(2, $DocComment->getTagValues('tag1'));
		$this->assertEquals('foo bar', $DocComment->getTagValues('tag1')[0]);
		$this->assertEquals('id', $DocComment->getTagValues('tag2')[0]['key']);
		$this->assertEquals('John Doo', $DocComment->getTagValues('tag2')[0]['name']);
		$this->assertEquals(5, $DocComment->getTagValues('tag2')[0]['level']);
		$this->assertTrue($DocComment->getTagValues('tag2')[0]['active']);
		$this->assertEquals('/[0-9]{1,}/', $DocComment->getTagValues('tag3')[0]['regex']);
	}
}
