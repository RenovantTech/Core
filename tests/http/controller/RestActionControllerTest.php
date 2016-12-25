<?php
namespace test\http\controller;
use metadigit\core\http\controller\ActionController,
	metadigit\core\http\Request,
	metadigit\core\http\Response;

class RestActionControllerTest extends \PHPUnit_Framework_TestCase {

	function testConstructor() {
		$ActionController = new \mock\http\controller\RestActionController;
		$this->assertInstanceOf('metadigit\core\http\ControllerInterface', $ActionController);
		$this->assertInstanceOf('metadigit\core\http\controller\ActionController', $ActionController);

		$ReflProp = new \ReflectionProperty('metadigit\core\http\controller\ActionController', '_actions');
		$ReflProp->setAccessible(true);
		$_actions = $ReflProp->getValue($ActionController);
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
	 */
	function testResolveActionMethod(\mock\http\controller\RestActionController $ActionController) {
		$ReflMethod = new \ReflectionMethod('metadigit\core\http\controller\ActionController', 'resolveActionMethod');
		$ReflMethod->setAccessible(true);

		$_SERVER['REQUEST_URI'] = '/book';
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$Req = new Request;
		$this->assertEquals('create', $ReflMethod->invoke($ActionController, $Req));

		$_SERVER['REQUEST_URI'] = '/book/14';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$Req = new Request;
		$this->assertEquals('read', $ReflMethod->invoke($ActionController, $Req));

		$_SERVER['REQUEST_URI'] = '/book';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$Req = new Request;
		$this->assertEquals('readAll', $ReflMethod->invoke($ActionController, $Req));

		$_SERVER['REQUEST_URI'] = '/book/14';
		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$Req = new Request;
		$this->assertEquals('update', $ReflMethod->invoke($ActionController, $Req));

		$_SERVER['REQUEST_URI'] = '/book/14';
		$_SERVER['REQUEST_METHOD'] = 'DELETE';
		$Req = new Request;
		$this->assertEquals('destroy', $ReflMethod->invoke($ActionController, $Req));

		return $ActionController;
	}

	/**
	 * @depends testResolveActionMethod
	 * @expectedException \metadigit\core\http\Exception
	 * @expectedExceptionCode 111
	 */
	function testResolveActionException() {
		$ActionController2 = new \mock\http\controller\RestActionController;
		$ReflMethod = new \ReflectionMethod('metadigit\core\http\controller\ActionController', 'resolveActionMethod');
		$ReflMethod->setAccessible(true);

		$_SERVER['REQUEST_METHOD'] = 'INVALID';
		$Req = new Request;
		$ReflMethod->invoke($ActionController2, $Req);
	}

	/**
	 * @depends testResolveActionMethod
	 */
	function testHandle(\mock\http\controller\RestActionController $ActionController) {
		$_SERVER['REQUEST_URI'] = '/book';
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST = ['id'=>53];
		$Req = new Request;
		$Res = new Response;
		$ActionController->handle($Req, $Res);
		$this->assertEquals('create', $Res->getView());
		$this->assertEquals('book', $Res->get('class'));
		$this->assertSame(53, $Res->get('id'));
		$_POST = [];

		$_SERVER['REQUEST_URI'] = '/book/32';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$Req = new Request;
		$Res = new Response;
		$ActionController->handle($Req, $Res);
		$this->assertEquals('read', $Res->getView());
		$this->assertEquals('book', $Res->get('class'));
		$this->assertSame(32, $Res->get('id'));

		$_SERVER['REQUEST_URI'] = '/book';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$Req = new Request;
		$Res = new Response;
		$ActionController->handle($Req, $Res);
		$this->assertEquals('readAll', $Res->getView());
		$this->assertEquals('book', $Res->get('class'));

		$_SERVER['REQUEST_URI'] = '/book/78';
		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$Req = new Request;
		$Res = new Response;
		$ActionController->handle($Req, $Res);
		$this->assertEquals('update', $Res->getView());
		$this->assertSame('book', $Res->get('class'));
		$this->assertSame(78, $Res->get('id'));

		$_SERVER['REQUEST_URI'] = '/book/41';
		$_SERVER['REQUEST_METHOD'] = 'DELETE';
		$Req = new Request;
		$Res = new Response;
		$ActionController->handle($Req, $Res);
		$this->assertEquals('destroy', $Res->getView());
		$this->assertSame('book', $Res->get('class'));
		$this->assertSame(41, $Res->get('id'));
	}
}
