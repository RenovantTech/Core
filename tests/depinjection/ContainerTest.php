<?php
namespace test\depinjection;
use metadigit\core\depinjection\Container,
	metadigit\core\depinjection\ContainerException;

class ContainerTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$Container = new Container('project.web', MOCK_DIR.'/depinjection/container.xml');
		$this->assertInstanceOf('metadigit\core\depinjection\Container', $Container);
		return $Container;
	}

	/**
	 * @depends testConstructor
	 * @expectedException		\metadigit\core\depinjection\ContainerException
	 * @expectedExceptionCode	11
	 */
	function testConstructorException() {
		new Container('project.web', __DIR__.'/containerNOTEXISTS.xml');
	}

	/**
	 * @depends testConstructor
	 */
	function testGet(Container $Container) {
		// only ID
		$Mock = $Container->get('mock.depinjection.Mock1');
		$this->assertInstanceOf('mock\depinjection\Mock1', $Mock);
		$ReflProp = new \ReflectionProperty('mock\depinjection\Mock1', 'name');
		$ReflProp->setAccessible(true);
		$name = $ReflProp->getValue($Mock);
		$this->assertEquals('Mock1', $name);
		// ID & class
		$Mock = $Container->get('mock.depinjection.Mock1','mock\depinjection\Mock1');
		$this->assertInstanceOf('mock\depinjection\Mock1', $Mock);
		$ReflProp = new \ReflectionProperty('mock\depinjection\Mock1', 'name');
		$ReflProp->setAccessible(true);
		$name = $ReflProp->getValue($Mock);
		$this->assertEquals('Mock1', $name);

		// only ID
		$Mock = $Container->get('mock.depinjection.Mock2');
		$this->assertInstanceOf('mock\depinjection\Mock2', $Mock);
		$ReflProp = new \ReflectionProperty('mock\depinjection\Mock2', 'name');
		$ReflProp->setAccessible(true);
		$name = $ReflProp->getValue($Mock);
		$this->assertEquals('Mock2', $name);
	}

	/**
	 * @depends testConstructor
	 * @expectedException		\metadigit\core\depinjection\ContainerException
	 * @expectedExceptionCode	1
	 */
	function testGetException(Container $Container) {
		$Container->get('mock.NotExists');
	}

	/**
	 * @depends testConstructor
	 */
	function testHas(Container $Container) {
		$this->assertTrue($Container->has('mock.depinjection.Mock1'));
		$this->assertFalse($Container->has('mock.depinjection.NotExists'));
		$this->assertTrue($Container->has('mock.depinjection.Mock1', 'mock\depinjection\Mock1'));
		$this->assertFalse($Container->has('mock.depinjection.Mock1', 'Exception'));
	}

	/**
	 * @depends testConstructor
	 */
	function testGetListByType(Container $Container) {
		$ids = $Container->getListByType('mock\depinjection\Mock1');
		$this->assertNotEmpty($ids);
		$this->assertEquals('mock.depinjection.Mock1', $ids[0]);
		$this->assertEmpty($Container->getListByType('Exception'));
	}

	/**
	 * @depends testConstructor
	 */
	function testGetAllByType(Container $Container) {
		$objs = $Container->getAllByType('mock\depinjection\Mock1');
		$this->assertNotEmpty($objs);
		$this->assertInstanceOf('mock\depinjection\Mock1', $objs[0]);
		$this->assertEmpty($Container->getAllByType('Exception'));
	}

	/**
	 * @depends testConstructor
	 */
	function testGetType(Container $Container) {
		$this->assertEquals('mock\depinjection\Mock1', $Container->getType('mock.depinjection.Mock1'));
	}

	/**
	 * @depends testConstructor
	 * @expectedException		\metadigit\core\depinjection\ContainerException
	 * @expectedExceptionCode	1
	 */
	function testGetTypeException(Container $Container) {
		$Container->getType('mock.depinjection.NotExists');
	}
}
