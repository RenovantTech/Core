<?php
namespace test\container;
use metadigit\core\container\Container;

class ContainerTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$Container = new Container('test.container');
		$this->assertInstanceOf('metadigit\core\container\Container', $Container);
		return $Container;
	}

	/**
	 * @depends testConstructor
	 * @expectedException		\metadigit\core\container\ContainerException
	 * @expectedExceptionCode	11
	 */
	function testConstructorException() {
		new Container('project.web');
	}

	/**
	 * @depends testConstructor
	 * @param Container $Container
	 */
	function testGet(Container $Container) {
		// only ID
		$Mock = $Container->get('test.container.Mock1');
		$this->assertInstanceOf('test\container\Mock1', $Mock);
		// string property
		$ReflProp = new \ReflectionProperty('test\container\Mock1', 'name');
		$ReflProp->setAccessible(true);
		$name = $ReflProp->getValue($Mock);
		$this->assertEquals('Mock1', $name);
		// array property
		$ReflProp = new \ReflectionProperty('test\container\Mock1', 'numbers');
		$ReflProp->setAccessible(true);
		$numbers = $ReflProp->getValue($Mock);
		$this->assertCount(3, $numbers);
		$this->assertContains(5, $numbers);
		// map property
		$ReflProp = new \ReflectionProperty('test\container\Mock1', 'preferences');
		$ReflProp->setAccessible(true);
		$preferences = $ReflProp->getValue($Mock);
		$this->assertCount(3, $preferences);
		$this->assertArrayHasKey('p1', $preferences);
		$this->assertEquals('hello', $preferences['p1']);
		$this->assertCount(2, $preferences['subpref']);
		$this->assertArrayHasKey('subpref', $preferences);
		$this->assertArrayHasKey('s1', $preferences['subpref']);
		$this->assertEquals('red', $preferences['subpref']['s1']);

		// ID & class
		$Mock = $Container->get('test.container.Mock1','test\container\Mock1');
		$this->assertInstanceOf('test\container\Mock1', $Mock);
		$ReflProp = new \ReflectionProperty('test\container\Mock1', 'name');
		$ReflProp->setAccessible(true);
		$name = $ReflProp->getValue($Mock);
		$this->assertEquals('Mock1', $name);

		// only ID
		$Mock2 = $Container->get('test.container.Mock2');
		$this->assertInstanceOf('test\container\Mock2', $Mock2);
		$this->assertEquals('Mock2', $Mock2->name());
		$this->assertInstanceOf('metadigit\core\CoreProxy', $Mock2->getChild());
		$this->assertEquals('SystemMock', $Mock2->getChild()->name());
	}

	/**
	 * @depends                  testConstructor
	 * @expectedException        \metadigit\core\container\ContainerException
	 * @expectedExceptionCode    1
	 * @param Container $Container
	 */
	function testGetException(Container $Container) {
		$Container->get('test.NotExists');
	}

	/**
	 * @depends testConstructor
	 * @param Container $Container
	 */
	function testHas(Container $Container) {
		$this->assertTrue($Container->has('test.container.Mock1'));
		$this->assertFalse($Container->has('test.container.NotExists'));
		$this->assertTrue($Container->has('test.container.Mock1', 'test\container\Mock1'));
		$this->assertFalse($Container->has('test.container.Mock1', 'Exception'));
	}

	/**
	 * @depends testConstructor
	 * @param Container $Container
	 */
	function testGetListByType(Container $Container) {
		$ids = $Container->getListByType('test\container\Mock1');
		$this->assertNotEmpty($ids);
		$this->assertEquals('test.container.Mock1', $ids[0]);
		$this->assertEmpty($Container->getListByType('Exception'));
	}

	/**
	 * @depends testConstructor
	 * @param Container $Container
	 */
	function testGetAllByType(Container $Container) {
		$objs = $Container->getAllByType('test\container\Mock1');
		$this->assertNotEmpty($objs);
		$this->assertInstanceOf('test\container\Mock1', $objs[0]);
		$this->assertEmpty($Container->getAllByType('Exception'));
	}

	/**
	 * @depends testConstructor
	 * @param Container $Container
	 */
	function testGetType(Container $Container) {
		$this->assertEquals('test\container\Mock1', $Container->getType('test.container.Mock1'));
	}

	/**
	 * @depends                  testConstructor
	 * @expectedException        \metadigit\core\container\ContainerException
	 * @expectedExceptionCode    1
	 * @param Container $Container
	 */
	function testGetTypeException(Container $Container) {
		$Container->getType('test.container.NotExists');
	}
}
