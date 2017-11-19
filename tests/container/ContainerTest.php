<?php
namespace test\container;
use metadigit\core\container\Container;

class ContainerTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$Container = new Container('mock.container');
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
		$Mock = $Container->get('mock.container.Mock1');
		$this->assertInstanceOf('mock\container\Mock1', $Mock);
		// string property
		$ReflProp = new \ReflectionProperty('mock\container\Mock1', 'name');
		$ReflProp->setAccessible(true);
		$name = $ReflProp->getValue($Mock);
		$this->assertEquals('Mock1', $name);
		// array property
		$ReflProp = new \ReflectionProperty('mock\container\Mock1', 'numbers');
		$ReflProp->setAccessible(true);
		$numbers = $ReflProp->getValue($Mock);
		$this->assertCount(3, $numbers);
		$this->assertContains(5, $numbers);
		// map property
		$ReflProp = new \ReflectionProperty('mock\container\Mock1', 'preferences');
		$ReflProp->setAccessible(true);
		$preferences = $ReflProp->getValue($Mock);
		$this->assertCount(2, $preferences);
		$this->assertArrayHasKey('p1', $preferences);
		$this->assertEquals('hello', $preferences['p1']);

		// ID & class
		$Mock = $Container->get('mock.container.Mock1','mock\container\Mock1');
		$this->assertInstanceOf('mock\container\Mock1', $Mock);
		$ReflProp = new \ReflectionProperty('mock\container\Mock1', 'name');
		$ReflProp->setAccessible(true);
		$name = $ReflProp->getValue($Mock);
		$this->assertEquals('Mock1', $name);

		// only ID
		$Mock2 = $Container->get('mock.container.Mock2');
		$this->assertInstanceOf('mock\container\Mock2', $Mock2);
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
		$Container->get('mock.NotExists');
	}

	/**
	 * @depends testConstructor
	 * @param Container $Container
	 */
	function testHas(Container $Container) {
		$this->assertTrue($Container->has('mock.container.Mock1'));
		$this->assertFalse($Container->has('mock.container.NotExists'));
		$this->assertTrue($Container->has('mock.container.Mock1', 'mock\container\Mock1'));
		$this->assertFalse($Container->has('mock.container.Mock1', 'Exception'));
	}

	/**
	 * @depends testConstructor
	 * @param Container $Container
	 */
	function testGetListByType(Container $Container) {
		$ids = $Container->getListByType('mock\container\Mock1');
		$this->assertNotEmpty($ids);
		$this->assertEquals('mock.container.Mock1', $ids[0]);
		$this->assertEmpty($Container->getListByType('Exception'));
	}

	/**
	 * @depends testConstructor
	 * @param Container $Container
	 */
	function testGetAllByType(Container $Container) {
		$objs = $Container->getAllByType('mock\container\Mock1');
		$this->assertNotEmpty($objs);
		$this->assertInstanceOf('mock\container\Mock1', $objs[0]);
		$this->assertEmpty($Container->getAllByType('Exception'));
	}

	/**
	 * @depends testConstructor
	 * @param Container $Container
	 */
	function testGetType(Container $Container) {
		$this->assertEquals('mock\container\Mock1', $Container->getType('mock.container.Mock1'));
	}

	/**
	 * @depends                  testConstructor
	 * @expectedException        \metadigit\core\container\ContainerException
	 * @expectedExceptionCode    1
	 * @param Container $Container
	 */
	function testGetTypeException(Container $Container) {
		$Container->getType('mock.container.NotExists');
	}
}
