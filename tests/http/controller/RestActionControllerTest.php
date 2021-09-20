<?php
namespace test\http\controller;
use renovant\core\http\Request,
	renovant\core\http\Response;

class RestActionControllerTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$ActionController = new \test\http\controller\RestActionController;
		$this->assertInstanceOf('renovant\core\http\ControllerInterface', $ActionController);
		$this->assertInstanceOf('renovant\core\http\controller\ActionController', $ActionController);

		$RefProp = new \ReflectionProperty('renovant\core\http\controller\ActionController', '_config');
		$RefProp->setAccessible(true);
		$_config = $RefProp->getValue($ActionController);
		$this->assertCount(5, $_config);

		$this->assertArrayHasKey('destroy', $_config);
		$this->assertEquals('id', $_config['destroy']['params'][3]['name']);
		$this->assertNull($_config['destroy']['params'][3]['class']);
		$this->assertEquals('integer', $_config['destroy']['params'][3]['type']);
		$this->assertFalse($_config['destroy']['params'][3]['optional']);
		$this->assertNull($_config['destroy']['params'][3]['default']);

		return $ActionController;
	}

	/**
	 * @depends testConstructor
	 * @param \test\http\controller\RestActionController $ActionController
	 * @return \test\http\controller\RestActionController
	 */
	function testResolveActionMethod(\test\http\controller\RestActionController $ActionController) {
		$RefMethod = new \ReflectionMethod('renovant\core\http\controller\ActionController', 'resolveActionMethod');
		$RefMethod->setAccessible(true);

		$_SERVER['REQUEST_URI'] = '/book';
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$Req = new Request;
		$Req->setAttribute('APP_MOD_CONTROLLER_URI', 'book');
		$this->assertEquals('create', $RefMethod->invoke($ActionController, $Req));

		$_SERVER['REQUEST_URI'] = '/book/14';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$Req = new Request;
		$Req->setAttribute('APP_MOD_CONTROLLER_URI', 'book/14');
		$this->assertEquals('read', $RefMethod->invoke($ActionController, $Req));

		$_SERVER['REQUEST_URI'] = '/book';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$Req = new Request;
		$Req->setAttribute('APP_MOD_CONTROLLER_URI', 'book');
		$this->assertEquals('readAll', $RefMethod->invoke($ActionController, $Req));

		$_SERVER['REQUEST_URI'] = '/book/14';
		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$Req = new Request;
		$Req->setAttribute('APP_MOD_CONTROLLER_URI', 'book/14');
		$this->assertEquals('update', $RefMethod->invoke($ActionController, $Req));

		$_SERVER['REQUEST_URI'] = '/book/14';
		$_SERVER['REQUEST_METHOD'] = 'DELETE';
		$Req = new Request;
		$Req->setAttribute('APP_MOD_CONTROLLER_URI', 'book/14');
		$this->assertEquals('destroy', $RefMethod->invoke($ActionController, $Req));

		return $ActionController;
	}

	/**
	 * @depends testResolveActionMethod
	 */
	function testResolveActionException() {
		$this->expectExceptionCode(111);
		$this->expectException(\renovant\core\http\Exception::class);
		$ActionController2 = new \test\http\controller\RestActionController;
		$RefMethod = new \ReflectionMethod('renovant\core\http\controller\ActionController', 'resolveActionMethod');
		$RefMethod->setAccessible(true);

		$_SERVER['REQUEST_METHOD'] = 'INVALID';
		$Req = new Request;
		$RefMethod->invoke($ActionController2, $Req);
	}

	/**
	 * @depends testResolveActionMethod
	 * @param \test\http\controller\RestActionController $ActionController
	 * @throws \renovant\core\context\ContextException
	 * @throws \renovant\core\event\EventDispatcherException
	 * @throws \renovant\core\http\Exception
	 */
	function testHandle(\test\http\controller\RestActionController $ActionController) {
		$_SERVER['REQUEST_URI'] = '/book';
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST = ['id'=>53];
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('APP_MOD_CONTROLLER_URI', 'book');
		$ActionController->handle($Req, $Res);
		$this->assertEquals(['create', null, null], $Res->getView());
		$this->assertEquals('book', $Res->get('class'));
		$this->assertSame(53, $Res->get('id'));
		$_POST = [];

		$_SERVER['REQUEST_URI'] = '/book/32';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('APP_MOD_CONTROLLER_URI', 'book/32');
		$ActionController->handle($Req, $Res);
		$this->assertEquals(['read', null, null], $Res->getView());
		$this->assertEquals('book', $Res->get('class'));
		$this->assertSame(32, $Res->get('id'));

		$_SERVER['REQUEST_URI'] = '/book';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('APP_MOD_CONTROLLER_URI', 'book');
		$ActionController->handle($Req, $Res);
		$this->assertEquals(['readAll', null, null], $Res->getView());
		$this->assertEquals('book', $Res->get('class'));

		$_SERVER['REQUEST_URI'] = '/book/78';
		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('APP_MOD_CONTROLLER_URI', 'book/78');
		$ActionController->handle($Req, $Res);
		$this->assertEquals(['update', null, null], $Res->getView());
		$this->assertSame('book', $Res->get('class'));
		$this->assertSame(78, $Res->get('id'));

		$_SERVER['REQUEST_URI'] = '/book/41';
		$_SERVER['REQUEST_METHOD'] = 'DELETE';
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('APP_MOD_CONTROLLER_URI', 'book/41');
		$ActionController->handle($Req, $Res);
		$this->assertEquals(['destroy', null, null], $Res->getView());
		$this->assertSame('book', $Res->get('class'));
		$this->assertSame(41, $Res->get('id'));
	}
}
