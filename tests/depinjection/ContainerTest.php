<?php
namespace test\depinjection;
use metadigit\core\depinjection\Container,
	metadigit\core\depinjection\ContainerException;

class ContainerTest extends \PHPUnit_Framework_TestCase {

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
		$Mock = $Container->get('mock.Mock');
		$this->assertInstanceOf('mock\GlobalMock', $Mock);
		$ReflProp = new \ReflectionProperty('mock\GlobalMock', 'name');
		$ReflProp->setAccessible(true);
		$name = $ReflProp->getValue($Mock);
		$this->assertEquals('LocalMock', $name);
		// ID & class
		$Mock = $Container->get('mock.Mock','mock\GlobalMock');
		$this->assertInstanceOf('mock\GlobalMock', $Mock);
		$ReflProp = new \ReflectionProperty('mock\GlobalMock', 'name');
		$ReflProp->setAccessible(true);
		$name = $ReflProp->getValue($Mock);
		$this->assertEquals('LocalMock', $name);
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
		$this->assertTrue($Container->has('mock.Mock'));
		$this->assertFalse($Container->has('mock.NotExists'));
		$this->assertTrue($Container->has('mock.Mock', 'mock\GlobalMock'));
		$this->assertFalse($Container->has('mock.Mock', 'Exception'));
	}

	/**
	 * @depends testConstructor
	 */
	function testGetListByType(Container $Container) {
		$ids = $Container->getListByType('mock\GlobalMock');
		$this->assertNotEmpty($ids);
		$this->assertEquals('mock.Mock', $ids[0]);
		$this->assertEmpty($Container->getListByType('Exception'));
	}

	/**
	 * @depends testConstructor
	 */
	function testGetAllByType(Container $Container) {
		$objs = $Container->getAllByType('mock\GlobalMock');
		$this->assertNotEmpty($objs);
		$this->assertInstanceOf('mock\GlobalMock', $objs[0]);
		$this->assertEmpty($Container->getAllByType('Exception'));
	}

	/**
	 * @depends testConstructor
	 */
	function testGetType(Container $Container) {
		$this->assertEquals('mock\GlobalMock', $Container->getType('mock.Mock'));
	}

	/**
	 * @depends testConstructor
	 * @expectedException		\metadigit\core\depinjection\ContainerException
	 * @expectedExceptionCode	1
	 */
	function testGetTypeException(Container $Container) {
		$Container->getType('mock.NotExists');
	}
}