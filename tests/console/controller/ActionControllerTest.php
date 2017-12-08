<?php
namespace test\console\controller;
use metadigit\core\console\controller\ActionController,
	metadigit\core\cli\Request,
	metadigit\core\cli\Response;

class ActionControllerTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$ActionController = new \test\console\controller\ActionController;
		$this->assertInstanceOf('metadigit\core\console\ControllerInterface', $ActionController);
		$this->assertInstanceOf('metadigit\core\console\controller\ActionController', $ActionController);

		$ReflProp = new \ReflectionProperty('metadigit\core\console\controller\ActionController', '_actions');
		$ReflProp->setAccessible(true);
		$_actions = $ReflProp->getValue($ActionController);
		$this->assertCount(6, $_actions);

		$this->assertArrayHasKey('bar', $_actions);
		$this->assertEmpty($_actions['bar']);

		$this->assertArrayHasKey('action2', $_actions);
		$this->assertEquals('id', $_actions['action2']['params'][2]['name']);
		$this->assertNull($_actions['action2']['params'][2]['class']);
		$this->assertEquals('integer', $_actions['action2']['params'][2]['type']);
		$this->assertFalse($_actions['action2']['params'][2]['optional']);
		$this->assertNull($_actions['action2']['params'][2]['default']);

		$this->assertArrayHasKey('action3', $_actions);
		$this->assertEquals('name', $_actions['action3']['params'][2]['name']);
		$this->assertNull($_actions['action3']['params'][2]['class']);
		$this->assertEquals('string', $_actions['action3']['params'][2]['type']);
		$this->assertTrue($_actions['action3']['params'][2]['optional']);
		$this->assertEquals('Tom', $_actions['action3']['params'][2]['default']);

		return $ActionController;
	}

	/**
	 * @depends testConstructor
	 */
	function testResolveActionMethod(\test\console\controller\ActionController $ActionController) {
		$ReflMethod = new \ReflectionMethod('metadigit\core\console\controller\ActionController', 'resolveActionMethod');
		$ReflMethod->setAccessible(true);

		$_SERVER['argv'] = ['sys','mod1'];
		$Req = new Request;
		$this->assertEquals('index', $ReflMethod->invoke($ActionController, $Req));

		$_SERVER['argv'] = ['sys','mod1','foo'];
		$Req = new Request;
		$this->assertEquals('foo', $ReflMethod->invoke($ActionController, $Req));

		$_SERVER['argv'] = ['sys','mod1','not-exists'];
		$Req = new Request;
		$this->assertEquals('fallback', $ReflMethod->invoke($ActionController, $Req));

		return $ActionController;
	}

	/**
	 * @depends testResolveActionMethod
	 * @expectedException \metadigit\core\console\Exception
	 * @expectedExceptionCode 111
	 */
	function testResolveActionException() {
		$ActionController2 = new \test\console\controller\ActionController2;
		$ReflMethod = new \ReflectionMethod('metadigit\core\console\controller\ActionController', 'resolveActionMethod');
		$ReflMethod->setAccessible(true);

		$_SERVER['argv'] = ['sys','mod1','not-exists'];
		$Req = new Request;
		$ReflMethod->invoke($ActionController2, $Req);
	}

	/**
	 * @depends testResolveActionMethod
	 */
	function testHandle(\test\console\controller\ActionController $ActionController) {
		$_SERVER['argv'] = ['sys','mod1','action2','--id=7'];
		$Req = new Request;
		$Res = new Response;
		$ActionController->handle($Req, $Res);
		$this->assertEquals('id-7', $Res->getView());
		$this->assertEquals(7, $Res->get('id'));

		$_SERVER['argv'] = ['sys','mod1','action3'];
		$Req = new Request;
		$Res = new Response;
		$ActionController->handle($Req, $Res);
		$this->assertEquals('view3', $Res->getView());
		$this->assertEquals('Tom', $Res->get('name'));

		$_SERVER['argv'] = ['sys','mod1','action3','--name=Jack'];
		$_GET['name'] = 'Jack';
		$Req = new Request;
		$Res = new Response;
		$ActionController->handle($Req, $Res);
		$this->assertEquals('view3', $Res->getView());
		$this->assertEquals('Jack', $Res->get('name'));
	}
}
