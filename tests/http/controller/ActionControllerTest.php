<?php
namespace test\http\controller;
use metadigit\core\http\Request,
	metadigit\core\http\Response;

class ActionControllerTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$ActionController = new \test\http\controller\ActionController;
		$this->assertInstanceOf('metadigit\core\http\ControllerInterface', $ActionController);
		$this->assertInstanceOf('metadigit\core\http\controller\ActionController', $ActionController);

		$RefProp = new \ReflectionProperty('metadigit\core\http\controller\ActionController', '_actions');
		$RefProp->setAccessible(true);
		$_actions = $RefProp->getValue($ActionController);
		$this->assertCount(7, $_actions);

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
	 * @param \test\http\controller\ActionController $ActionController
	 * @return \test\http\controller\ActionController
	 */
	function testResolveActionMethod(\test\http\controller\ActionController $ActionController) {
		$RefMethod = new \ReflectionMethod('metadigit\core\http\controller\ActionController', 'resolveActionMethod');
		$RefMethod->setAccessible(true);

		$_SERVER['REQUEST_URI'] = '/mod1/';
		$Req = new Request;
		$this->assertEquals('index', $RefMethod->invoke($ActionController, $Req));

		$_SERVER['REQUEST_URI'] = '/mod1/foo';
		$Req = new Request;
		$this->assertEquals('foo', $RefMethod->invoke($ActionController, $Req));

		$_SERVER['REQUEST_URI'] = '31/02/2013/details-xml';
		$Req = new Request;
		$this->assertEquals('details', $RefMethod->invoke($ActionController, $Req));

		return $ActionController;
	}

	/**
	 * @depends testResolveActionMethod
	 * @expectedException \metadigit\core\http\Exception
	 * @expectedExceptionCode 111
	 */
	function testResolveActionException() {
		$ActionController2 = new \test\http\controller\ActionController;
		$RefMethod = new \ReflectionMethod('metadigit\core\http\controller\ActionController', 'resolveActionMethod');
		$RefMethod->setAccessible(true);

		$_SERVER['REQUEST_URI'] = '/mod1/not-exists';
		$Req = new Request;
		$RefMethod->invoke($ActionController2, $Req);
	}

	/**
	 * @depends testResolveActionMethod
	 * @param \test\http\controller\ActionController $ActionController
	 */
	function testHandle(\test\http\controller\ActionController $ActionController) {
		$_SERVER['REQUEST_URI'] = '/mod1/action2';
		$_GET['id'] = 7;
		$Req = new Request;
		$Res = new Response;
		$ActionController->handle($Req, $Res);
		$this->assertEquals(['id-7', null, null], $Res->getView());
		$this->assertEquals(7, $Res->get('id'));

		$_SERVER['REQUEST_URI'] = '/mod1/action3';
		unset($_GET['name']);
		$Req = new Request;
		$Res = new Response;
		$ActionController->handle($Req, $Res);
		$this->assertEquals(['view3', null, null], $Res->getView());
		$this->assertEquals('Tom', $Res->get('name'));

		$_SERVER['REQUEST_URI'] = '/mod1/action3';
		$_GET['name'] = 'Jack';
		$Req = new Request;
		$Res = new Response;
		$ActionController->handle($Req, $Res);
		$this->assertEquals(['view3', null, null], $Res->getView());
		$this->assertEquals('Jack', $Res->get('name'));

		$_SERVER['REQUEST_URI'] = '31/02/2013/details-xml';
		$Req = new Request;
		$Res = new Response;
		$ActionController->handle($Req, $Res);
		$this->assertEquals(['details', null, null], $Res->getView());
		$this->assertSame(2013, $Res->get('year'));
		$this->assertSame(2, $Res->get('month'));
		$this->assertSame(31, $Res->get('day'));
		$this->assertEquals('xml', $Res->get('format'));
	}
}
