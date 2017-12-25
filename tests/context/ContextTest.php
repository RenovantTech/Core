<?php
namespace test\context;
use metadigit\core\sys,
	metadigit\core\container\Container,
	metadigit\core\context\Context;

class ContextTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$Context = new Context('test.context');
		$this->assertInstanceOf(Context::class, $Context);

		$ReflProp = new \ReflectionProperty(Context::class, 'namespace');
		$ReflProp->setAccessible(true);
		$namespace = $ReflProp->getValue($Context);
		$this->assertEquals('test.context', $namespace);

		$ReflProp = new \ReflectionProperty(Context::class, 'includedNamespaces');
		$ReflProp->setAccessible(true);
		$includedNamespaces = $ReflProp->getValue($Context);
		$this->assertContains('system', $includedNamespaces);
		$this->assertNotContains('foo', $includedNamespaces);

		$ReflProp = new \ReflectionProperty(Context::class, 'id2classMap');
		$ReflProp->setAccessible(true);
		$id2classMap = $ReflProp->getValue($Context);
		$this->assertArrayHasKey('test.context.Mock1', $id2classMap);
		$this->assertArrayNotHasKey('test.context.NotExists', $id2classMap);

		$ReflProp = new \ReflectionProperty(Context::class, 'listeners');
		$ReflProp->setAccessible(true);
		$listeners = $ReflProp->getValue($Context);
		$this->assertCount(2, $listeners);
		$this->assertArrayHasKey('event1', $listeners);
		$this->assertArrayHasKey('event2', $listeners);
		$this->assertCount(1, $listeners['event1']);
		$this->assertCount(1, $listeners['event2']);
		$this->assertEquals('test.context.Mock1->onEvent1', $listeners['event1'][2][0]);
		$this->assertEquals('test.context.Mock1->onEvent2', $listeners['event2'][1][0]);
		$this->assertEquals('test.context.Mock1->onEvent2bis', $listeners['event2'][1][1]);

		return $Context;
	}

	/**
	 * Test normal Context namespace
	 * @depends testConstructor
	 */
	function testFactory() {
		$Context = Context::factory('test.context');
		$this->assertInstanceOf(Context::class, $Context);
		$this->assertInstanceOf(Context::class, sys::cache('sys')->get('test.context.Context'));
		return $Context;
	}

	function testFactoryWithCycledGraphs() {
		$Context = Context::factory('test.context.cyclic1');
		$this->assertInstanceOf(Context::class, $Context);
	}

	/**
	 * @depends testFactory
	 * @param Context $Context
	 */
	function testHas(Context $Context) {
		$this->assertTrue($Context->has('test.context.Mock1', 'test\context\Mock1'));
		$this->assertFalse($Context->has('test.context.NotExists'));
	}

	/**
	 * Test global Context namespace
	 * @depends testConstructor
	 */
	function testFactory2() {
		$GlobalContext = Context::factory('system');
		$this->assertInstanceOf(Context::class, $GlobalContext);
		$this->assertTrue(sys::cache('sys')->has('system.Context'));
		return $GlobalContext;
	}

	/**
	 * @depends testFactory2
	 * @param Context $GlobalContext
	 */
	function testHas2(Context $GlobalContext) {
		$this->assertTrue($GlobalContext->has('system.Mock', 'test\GlobalMock'));
		$this->assertFalse($GlobalContext->has('system.NotExists'));
	}

	/**
	 * @depends testFactory
	 * @param Context $Context
	 */
	function testGetContainer(Context $Context) {
		$ReflMethod = new \ReflectionMethod(Context::class, 'getContainer');
		$ReflMethod->setAccessible(true);
		$this->assertInstanceOf(Container::class, $ReflMethod->invoke($Context));
		$this->assertTrue(sys::cache('sys')->has('test.context.Container'));
	}

	/**
	 * Test GET inside Context
	 * @depends testFactory
	 * @param Context $Context
	 * @return Context
	 */
	function testGet(Context $Context) {
		$Mock = $Context->get('test.context.Mock1');
		$this->assertEquals('foo', $Mock->getProp1());
		$this->assertEquals('bar', $Mock->getProp2());
		$this->assertInstanceOf('metadigit\core\CoreProxy', $Mock->getChild());
		$this->assertEquals('SystemMock', $Mock->getChild()->name());
		return $Context;
	}

	/**
	 * Test GET on included Contexts via ObjectProxy
	 * @depends testGet
	 * @param Context $Context
	 */
	function testGet2(Context $Context) {
		$Mock = $Context->get('test.context.Mock1');
		$this->assertEquals('Hello', $Mock->getChild()->hello());
	}

	/**
	 * Test GET Exception inside Context
	 * @depends                  testGet
	 * @expectedException        \metadigit\core\context\ContextException
	 * @expectedExceptionCode    1
	 * @param Context $Context
	 */
	function testGetException1(Context $Context) {
		$Context->get('test.context.NotExists');
	}

	/**
	 * Test GET Exception on included Contexts
	 * @depends                  testGet
	 * @expectedException        \metadigit\core\context\ContextException
	 * @expectedExceptionCode    1
	 * @param Context $Context
	 */
	function testGetException2(Context $Context) {
		$Context->get('system.NotExists');
	}
}
