<?php
namespace test\console\controller;
use metadigit\core\console\ControllerInterface,
	metadigit\core\console\controller\ActionController,
	metadigit\core\console\Request,
	metadigit\core\console\Response;

class ActionControllerTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$ActionController = new \test\console\controller\ActionController;
		$this->assertInstanceOf(ControllerInterface::class, $ActionController);
		$this->assertInstanceOf(ActionController::class, $ActionController);

		$RefProp = new \ReflectionProperty(ActionController::class, '_config');
		$RefProp->setAccessible(true);
		$_config = $RefProp->getValue($ActionController);
		$this->assertCount(6, $_config);

		$this->assertArrayHasKey('bar', $_config);
		$this->assertCount(1, $_config['bar']['params']);

		$this->assertArrayHasKey('action2', $_config);
		$this->assertEquals('id', $_config['action2']['params'][1]['name']);
		$this->assertNull($_config['action2']['params'][1]['class']);
		$this->assertEquals('integer', $_config['action2']['params'][1]['type']);
		$this->assertFalse($_config['action2']['params'][1]['optional']);
		$this->assertNull($_config['action2']['params'][1]['default']);

		$this->assertArrayHasKey('action3', $_config);
		$this->assertEquals('name', $_config['action3']['params'][1]['name']);
		$this->assertNull($_config['action3']['params'][1]['class']);
		$this->assertEquals('string', $_config['action3']['params'][1]['type']);
		$this->assertTrue($_config['action3']['params'][1]['optional']);
		$this->assertEquals('Tom', $_config['action3']['params'][1]['default']);

		return $ActionController;
	}

	/**
	 * @depends testConstructor
	 * @param \test\console\controller\ActionController $ActionController
	 * @return \test\console\controller\ActionController
	 */
	function testResolveActionMethod(\test\console\controller\ActionController $ActionController) {
		$RefMethod = new \ReflectionMethod(ActionController::class, 'resolveActionMethod');
		$RefMethod->setAccessible(true);

		$_SERVER['argv'] = ['sys','mod1'];
		$Req = new Request;
		$this->assertEquals('index', $RefMethod->invoke($ActionController, $Req));

		$_SERVER['argv'] = ['sys','mod1','foo'];
		$Req = new Request;
		$this->assertEquals('foo', $RefMethod->invoke($ActionController, $Req));

		$_SERVER['argv'] = ['sys','mod1','not-exists'];
		$Req = new Request;
		$this->assertEquals('fallback', $RefMethod->invoke($ActionController, $Req));

		return $ActionController;
	}

	/**
	 * @depends testResolveActionMethod
	 * @expectedException \metadigit\core\console\Exception
	 * @expectedExceptionCode 111
	 */
	function testResolveActionException() {
		$ActionController2 = new \test\console\controller\ActionController2;
		$RefMethod = new \ReflectionMethod(ActionController::class, 'resolveActionMethod');
		$RefMethod->setAccessible(true);

		$_SERVER['argv'] = ['sys','mod1','not-exists'];
		$Req = new Request;
		$RefMethod->invoke($ActionController2, $Req);
	}

	/**
	 * @depends testResolveActionMethod
	 * @param \test\console\controller\ActionController $ActionController
	 * @throws \metadigit\core\console\Exception
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
