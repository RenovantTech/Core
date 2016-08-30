<?php
namespace test\context;
use metadigit\core\Kernel,
	metadigit\core\context\Context;

class ContextTest extends \PHPUnit_Framework_TestCase {

	function testConstructor() {
		$Context = new Context('mock.context');
		$this->assertInstanceOf('metadigit\core\context\Context', $Context);

		$ReflProp = new \ReflectionProperty('metadigit\core\context\Context', 'namespace');
		$ReflProp->setAccessible(true);
		$namespace = $ReflProp->getValue($Context);
		$this->assertEquals('mock.context', $namespace);

		$ReflProp = new \ReflectionProperty('metadigit\core\context\Context', 'includedNamespaces');
		$ReflProp->setAccessible(true);
		$includedNamespaces = $ReflProp->getValue($Context);
		$this->assertContains('system', $includedNamespaces);
		$this->assertNotContains('foo', $includedNamespaces);

		$ReflProp = new \ReflectionProperty('metadigit\core\context\Context', 'id2classMap');
		$ReflProp->setAccessible(true);
		$id2classMap = $ReflProp->getValue($Context);
		$this->assertArrayHasKey('mock.context.Mock1', $id2classMap);
		$this->assertArrayNotHasKey('mock.context.NotExists', $id2classMap);

		$ReflProp = new \ReflectionProperty('metadigit\core\context\Context', 'listeners');
		$ReflProp->setAccessible(true);
		$listeners = $ReflProp->getValue($Context);
		$this->assertCount(2, $listeners);
		$this->assertArrayHasKey('event1', $listeners);
		$this->assertArrayHasKey('event2', $listeners);
		$this->assertCount(2, $listeners['event1']);
		$this->assertCount(1, $listeners['event2']);
		$this->assertEquals('mock.context.EventSubscriber->onEvent1', $listeners['event1'][1][0]);
		$this->assertEquals('mock.context.Mock1->onEvent1', $listeners['event1'][2][0]);
		$this->assertEquals('mock.context.EventSubscriber->onEvent2', $listeners['event2'][1][0]);

		return $Context;
	}

	/**
	 * Test normal Context namespace
	 * @depends testConstructor
	 */
	function testFactory() {
		$Context = Context::factory('mock.context');
		$this->assertInstanceOf('metadigit\core\context\Context', $Context);
		$this->assertInstanceOf('metadigit\core\context\Context', Kernel::cache('kernel')->get('mock.context.Context'));
		return $Context;
	}

	function testFactoryWithCycledGraphs() {
		Context::factory('mock.context.cyclic1');
	}

	/**
	 * @depends testFactory
	 * @param Context $Context
	 */
	function testHas(Context $Context) {
		$this->assertTrue($Context->has('mock.context.Mock1', 'mock\context\Mock1'));
		$this->assertFalse($Context->has('mock.context.NotExists'));
	}

	/**
	 * Test global Context namespace
	 * @depends testConstructor
	 */
	function testFactory2() {
		$GlobalContext = Context::factory('system');
		$this->assertInstanceOf('metadigit\core\context\Context', $GlobalContext);
		$this->assertTrue(Kernel::cache('kernel')->has('system.Context'));
		return $GlobalContext;
	}

	/**
	 * @depends testFactory2
	 * @param Context $GlobalContext
	 */
	function testHas2(Context $GlobalContext) {
		$this->assertTrue($GlobalContext->has('system.Mock', 'mock\GlobalMock'));
		$this->assertFalse($GlobalContext->has('system.NotExists'));
	}

	/**
	 * @depends testFactory
	 * @param Context $Context
	 */
	function testGetContainer(Context $Context) {
		$ReflMethod = new \ReflectionMethod('metadigit\core\context\Context', 'getContainer');
		$ReflMethod->setAccessible(true);
		$this->assertInstanceOf('metadigit\core\container\Container', $ReflMethod->invoke($Context));
		$this->assertTrue(Kernel::cache('kernel')->has('mock.context.Container'));
	}

	/**
	 * Test GET inside Context
	 * @depends testFactory
	 * @param Context $Context
	 * @return Context
	 */
	function testGet(Context $Context) {
		$Mock = $Context->get('mock.context.Mock1');
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
		$Mock = $Context->get('mock.context.Mock1');
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
		$Context->get('mock.context.NotExists');
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

	/**
	 * @depends testConstructor
	 * @param Context $Context
	 */
	function testAddListener(Context $Context) {
		$Context->listen('foo', 'callback1');
		$Context->listen('foo', 'callback2');
		$Context->listen('foo', 'callback0', 2);
		$Context->listen('bar', 'callback1');
		$Context->listen('bar', 'callback2');
		$Context->listen('bar', 'callback0', 2);
		$ReflProp = new \ReflectionProperty('metadigit\core\context\Context', 'listeners');
		$ReflProp->setAccessible(true);
		$listeners = $ReflProp->getValue($Context);
		$this->assertCount(4, $listeners);
		$this->assertArrayHasKey('foo', $listeners);
		$this->assertEquals('callback1', $listeners['foo'][1][0]);
		$this->assertEquals('callback2', $listeners['foo'][1][1]);
		$this->assertEquals('callback0', $listeners['foo'][2][0]);
	}

	/**
	 * @depends testConstructor
	 * @param Context $Context
	 */
	function testTrigger(Context $Context) {
		global $var;
		$var = 1;
		$Context->listen('trigger1', function($Ev) use (&$var) { $var++; });
		$Context->listen('trigger1', function($Ev) use (&$var) { $var = $var + 2; }, 2);
		$this->assertEquals(1, $var);
		$Context->trigger('trigger1', $Context);
		$this->assertEquals(4, $var);

		$var = 2;
		$Context->listen('trigger2', 'mock.context.Mock1->onEvent1');
		$Context->trigger('trigger2', $Context);
		$this->assertEquals(3, $var);

		$Context->listen('trigger3', function($Ev) { $Ev->stopPropagation(); });
		$Event = $Context->trigger('trigger3', $Context);
		$this->assertInstanceOf('metadigit\core\event\Event', $Event);
		$this->assertTrue($Event->isPropagationStopped());
	}
}
