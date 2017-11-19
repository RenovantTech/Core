<?php
namespace test\http\controller;
use metadigit\core\http\Request,
	metadigit\core\http\Response;

class RestActionControllerTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$ActionController = new \mock\http\controller\RestActionController;
		$this->assertInstanceOf('metadigit\core\http\ControllerInterface', $ActionController);
		$this->assertInstanceOf('metadigit\core\http\controller\ActionController', $ActionController);

		$RefProp = new \ReflectionProperty('metadigit\core\http\controller\ActionController', '_actions');
		$RefProp->setAccessible(true);
		$_actions = $RefProp->getValue($ActionController);
		$this->assertCount(5, $_actions);

		$this->assertArrayHasKey('destroy', $_actions);
		$this->assertEquals('id', $_actions['destroy']['params'][3]['name']);
		$this->assertNull($_actions['destroy']['params'][3]['class']);
		$this->assertEquals('integer', $_actions['destroy']['params'][3]['type']);
		$this->assertFalse($_actions['destroy']['params'][3]['optional']);
		$this->assertNull($_actions['destroy']['params'][3]['default']);

		return $ActionController;
	}

	/**
	 * @depends testConstructor
	 * @param \mock\http\controller\RestActionController $ActionController
	 * @return \mock\http\controller\RestActionController
	 */
	function testResolveActionMethod(\mock\http\controller\RestActionController $ActionController) {
		$RefMethod = new \ReflectionMethod('metadigit\core\http\controller\ActionController', 'resolveActionMethod');
		$RefMethod->setAccessible(true);

		$_SERVER['REQUEST_URI'] = '/book';
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$Req = new Request;
		$this->assertEquals('create', $RefMethod->invoke($ActionController, $Req));

		$_SERVER['REQUEST_URI'] = '/book/14';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$Req = new Request;
		$this->assertEquals('read', $RefMethod->invoke($ActionController, $Req));

		$_SERVER['REQUEST_URI'] = '/book';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$Req = new Request;
		$this->assertEquals('readAll', $RefMethod->invoke($ActionController, $Req));

		$_SERVER['REQUEST_URI'] = '/book/14';
		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$Req = new Request;
		$this->assertEquals('update', $RefMethod->invoke($ActionController, $Req));

		$_SERVER['REQUEST_URI'] = '/book/14';
		$_SERVER['REQUEST_METHOD'] = 'DELETE';
		$Req = new Request;
		$this->assertEquals('destroy', $RefMethod->invoke($ActionController, $Req));

		return $ActionController;
	}

	/**
	 * @depends testResolveActionMethod
	 * @expectedException \metadigit\core\http\Exception
	 * @expectedExceptionCode 111
	 */
	function testResolveActionException() {
		$ActionController2 = new \mock\http\controller\RestActionController;
		$RefMethod = new \ReflectionMethod('metadigit\core\http\controller\ActionController', 'resolveActionMethod');
		$RefMethod->setAccessible(true);

		$_SERVER['REQUEST_METHOD'] = 'INVALID';
		$Req = new Request;
		$RefMethod->invoke($ActionController2, $Req);
	}

	/**
	 * @depends testResolveActionMethod
	 * @param \mock\http\controller\RestActionController $ActionController
	 */
	function testHandle(\mock\http\controller\RestActionController $ActionController) {
		$_SERVER['REQUEST_URI'] = '/book';
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST = ['id'=>53];
		$Req = new Request;
		$Res = new Response;
		$ActionController->handle($Req, $Res);
		$this->assertEquals(['create', null, null], $Res->getView());
		$this->assertEquals('book', $Res->get('class'));
		$this->assertSame(53, $Res->get('id'));
		$_POST = [];

		$_SERVER['REQUEST_URI'] = '/book/32';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$Req = new Request;
		$Res = new Response;
		$ActionController->handle($Req, $Res);
		$this->assertEquals(['read', null, null], $Res->getView());
		$this->assertEquals('book', $Res->get('class'));
		$this->assertSame(32, $Res->get('id'));

		$_SERVER['REQUEST_URI'] = '/book';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$Req = new Request;
		$Res = new Response;
		$ActionController->handle($Req, $Res);
		$this->assertEquals(['readAll', null, null], $Res->getView());
		$this->assertEquals('book', $Res->get('class'));

		$_SERVER['REQUEST_URI'] = '/book/78';
		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$Req = new Request;
		$Res = new Response;
		$ActionController->handle($Req, $Res);
		$this->assertEquals(['update', null, null], $Res->getView());
		$this->assertSame('book', $Res->get('class'));
		$this->assertSame(78, $Res->get('id'));

		$_SERVER['REQUEST_URI'] = '/book/41';
		$_SERVER['REQUEST_METHOD'] = 'DELETE';
		$Req = new Request;
		$Res = new Response;
		$ActionController->handle($Req, $Res);
		$this->assertEquals(['destroy', null, null], $Res->getView());
		$this->assertSame('book', $Res->get('class'));
		$this->assertSame(41, $Res->get('id'));
	}
}
