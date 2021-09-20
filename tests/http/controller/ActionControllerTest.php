<?php
namespace test\http\controller;
use renovant\core\http\Request,
	renovant\core\http\Response;

class ActionControllerTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$ActionController = new \test\http\controller\ActionController;
		$this->assertInstanceOf('renovant\core\http\ControllerInterface', $ActionController);
		$this->assertInstanceOf('renovant\core\http\controller\ActionController', $ActionController);

		$RefProp = new \ReflectionProperty('renovant\core\http\controller\ActionController', '_config');
		$RefProp->setAccessible(true);
		$_config = $RefProp->getValue($ActionController);
		$this->assertCount(7, $_config);

		$this->assertArrayHasKey('index', $_config);
		$this->assertEquals('*', $_config['index']['method']);
		$this->assertEquals('/^$/', $_config['index']['pattern']);

		$this->assertArrayHasKey('foo', $_config);
		$this->assertEquals('*', $_config['foo']['method']);
		$this->assertEquals('/^foo$/', $_config['foo']['pattern']);

		$this->assertArrayHasKey('bar', $_config);
		$this->assertEquals('*', $_config['bar']['method']);
		$this->assertEquals('/^bar$/', $_config['bar']['pattern']);

		$this->assertArrayHasKey('action2', $_config);
		$this->assertEquals('id', $_config['action2']['params'][2]['name']);
		$this->assertNull($_config['action2']['params'][2]['class']);
		$this->assertEquals('integer', $_config['action2']['params'][2]['type']);
		$this->assertFalse($_config['action2']['params'][2]['optional']);
		$this->assertNull($_config['action2']['params'][2]['default']);

		$this->assertArrayHasKey('action3', $_config);
		$this->assertEquals('name', $_config['action3']['params'][2]['name']);
		$this->assertNull($_config['action3']['params'][2]['class']);
		$this->assertEquals('string', $_config['action3']['params'][2]['type']);
		$this->assertTrue($_config['action3']['params'][2]['optional']);
		$this->assertEquals('Tom', $_config['action3']['params'][2]['default']);

		return $ActionController;
	}

	/**
	 * @depends testConstructor
	 * @param \test\http\controller\ActionController $ActionController
	 * @return \test\http\controller\ActionController
	 */
	function testResolveActionMethod(\test\http\controller\ActionController $ActionController) {
		$RefMethod = new \ReflectionMethod('renovant\core\http\controller\ActionController', 'resolveActionMethod');
		$RefMethod->setAccessible(true);

		$_SERVER['REQUEST_URI'] = '/action/';
		$Req = new Request;
		$Req->setAttribute('APP_MOD_CONTROLLER_URI', '');
		$this->assertEquals('index', $RefMethod->invoke($ActionController, $Req));

		$_SERVER['REQUEST_URI'] = '/action/foo';
		$Req = new Request;
		$Req->setAttribute('APP_MOD_CONTROLLER_URI', 'foo');
		$this->assertEquals('foo', $RefMethod->invoke($ActionController, $Req));

		$_SERVER['REQUEST_URI'] = '/action/31/02/2013/details-xml';
		$Req = new Request;
		$Req->setAttribute('APP_MOD_CONTROLLER_URI', '31/02/2013/details-xml');
		$this->assertEquals('details', $RefMethod->invoke($ActionController, $Req));

		return $ActionController;
	}

	/**
	 * @depends testResolveActionMethod
	 */
	function testResolveActionException() {
		$this->expectExceptionCode(111);
		$this->expectException(\renovant\core\http\Exception::class);
		$ActionController2 = new \test\http\controller\ActionController;
		$RefMethod = new \ReflectionMethod('renovant\core\http\controller\ActionController', 'resolveActionMethod');
		$RefMethod->setAccessible(true);

		$_SERVER['REQUEST_URI'] = '/action/not-exists';
		$Req = new Request;
		$Req->setAttribute('APP_MOD_CONTROLLER_URI', 'not-exists');
		$RefMethod->invoke($ActionController2, $Req);
	}

	/**
	 * @depends testResolveActionMethod
	 * @param \test\http\controller\ActionController $ActionController
	 * @throws \renovant\core\context\ContextException
	 * @throws \renovant\core\event\EventDispatcherException
	 * @throws \renovant\core\http\Exception
	 */
	function testHandle(\test\http\controller\ActionController $ActionController) {
		$_SERVER['REQUEST_URI'] = '/action/action2';
		$_GET['id'] = 7;
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('APP_MOD_CONTROLLER_URI', 'action2');
		$ActionController->handle($Req, $Res);
		$this->assertEquals(['id-7', null, null], $Res->getView());
		$this->assertEquals(7, $Res->get('id'));

		$_SERVER['REQUEST_URI'] = '/action/action3';
		unset($_GET['name']);
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('APP_MOD_CONTROLLER_URI', 'action3');
		$ActionController->handle($Req, $Res);
		$this->assertEquals(['view3', null, null], $Res->getView());
		$this->assertEquals('Tom', $Res->get('name'));

		$_SERVER['REQUEST_URI'] = '/action/action3';
		$_GET['name'] = 'Jack';
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('APP_MOD_CONTROLLER_URI', 'action3');
		$ActionController->handle($Req, $Res);
		$this->assertEquals(['view3', null, null], $Res->getView());
		$this->assertEquals('Jack', $Res->get('name'));

		$_SERVER['REQUEST_URI'] = '31/02/2013/details-xml';
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('APP_MOD_CONTROLLER_URI', '31/02/2013/details-xml');
		$ActionController->handle($Req, $Res);
		$this->assertEquals(['details', null, null], $Res->getView());
		$this->assertSame(2013, $Res->get('year'));
		$this->assertSame(2, $Res->get('month'));
		$this->assertSame(31, $Res->get('day'));
		$this->assertEquals('xml', $Res->get('format'));
	}
}
