<?php
namespace test\context;
use metadigit\core\container\Container,
	metadigit\core\container\ContainerException,
	metadigit\core\context\Context,
	metadigit\core\context\ContextException,
	metadigit\core\event\EventDispatcher,
	metadigit\core\event\EventDispatcherException;

class ContextTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$Context = new Context(new Container, new EventDispatcher);
		$this->assertInstanceOf(Context::class, $Context);
		return $Context;
	}

	/**
	 * @depends testConstructor
	 * @param Context $Context
	 * @return Context
	 * @throws ContainerException
	 * @throws EventDispatcherException
	 * @throws ContextException
	 */
	function testInit(Context $Context) {
		/** @noinspection PhpVoidFunctionResultUsedInspection */
		$this->assertNull($Context->init('test.context'));
		return $Context;
	}

	/**
	 * @depends testConstructor
	 * @param Context $Context
	 * @throws EventDispatcherException
	 * @throws ContainerException
	 */
	function testInitException(Context $Context) {
		try {
			$Context->init('test.xxxxxxx');
			$this->fail('Expected ContainerException not thrown');
		} catch(ContextException $Ex) {
			$this->assertEquals(11, $Ex->getCode());
			$this->assertRegExp('/YAML config file NOT FOUND/', $Ex->getMessage());
		}
	}

	/**
	 * @depends testInit
	 * @param Context $Context
	 */
	function testHas(Context $Context) {
		$this->assertTrue($Context->has('test.context.Mock1', 'test\context\Mock1'));
		$this->assertFalse($Context->has('test.context.NotExists'));
	}

	/**
	 * Test GET inside Context
	 * @depends testInit
	 * @param Context $Context
	 * @throws ContextException
	 * @throws EventDispatcherException
	 */
	function testGet(Context $Context) {
		$Mock = $Context->get('test.context.Mock1');
		$this->assertEquals('foo', $Mock->getProp1());
		$this->assertEquals('bar', $Mock->getProp2());
		$this->assertInstanceOf('metadigit\core\CoreProxy', $Mock->getChild());
		$this->assertEquals('SystemMock', $Mock->getChild()->name());

		// Test GET on included Contexts via ObjectProxy
		$Mock = $Context->get('test.context.Mock1');
		$this->assertEquals('Hello', $Mock->getChild()->hello());
	}

	/**
	 * Test GET Exception inside Context
	 * @depends testInit
	 * @param Context $Context
	 * @throws EventDispatcherException
	 */
	function testGetException1(Context $Context) {
		try {
			$Context->get('test.context.NotExists');
			$this->fail('Expected ContextException not thrown');
		} catch(ContextException $Ex) {
			$this->assertEquals(1, $Ex->getCode());
			$this->assertRegExp('/is NOT defined/', $Ex->getMessage());
		}
	}
}
