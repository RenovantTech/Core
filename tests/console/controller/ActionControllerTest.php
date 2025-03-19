<?php
namespace test\console\controller;

use renovant\core\console\ControllerInterface,
renovant\core\console\controller\ActionController,
renovant\core\console\Request,
renovant\core\console\Response;

class ActionControllerTest extends \PHPUnit\Framework\TestCase {
	public function testConstructor() {
		$ActionController = new \test\console\controller\ActionController();
		$this->assertInstanceOf(ControllerInterface::class, $ActionController);
		$this->assertInstanceOf(ActionController::class, $ActionController);

		$RefProp = new \ReflectionProperty(ActionController::class, '_config');
		$RefProp->setAccessible(true);
		$_config = $RefProp->getValue($ActionController);
		$this->assertCount(7, $_config);

		// index()
		$this->assertArrayHasKey('index', $_config);
		$this->assertEquals('Res', $_config['index']['params'][0]['name']);
		$this->assertEquals(Response::class, $_config['index']['params'][0]['class']);
		$this->assertNull($_config['index']['params'][0]['type']);
		$this->assertFalse($_config['index']['params'][0]['optional']);

		// bar()
		$this->assertArrayHasKey('bar', $_config);
		$this->assertCount(1, $_config['bar']['params']);

		// action2()
		$this->assertArrayHasKey('action2', $_config);
		$this->assertEquals('id', $_config['action2']['params'][1]['name']);
		$this->assertNull($_config['action2']['params'][1]['class']);
		$this->assertEquals('int', $_config['action2']['params'][1]['type']);
		$this->assertFalse($_config['action2']['params'][1]['optional']);
		$this->assertNull($_config['action2']['params'][1]['default']);

		// action3()
		$this->assertArrayHasKey('action3', $_config);
		$this->assertEquals('name', $_config['action3']['params'][1]['name']);
		$this->assertNull($_config['action3']['params'][1]['class']);
		$this->assertEquals('string', $_config['action3']['params'][1]['type']);
		$this->assertTrue($_config['action3']['params'][1]['optional']);
		$this->assertEquals('Tom', $_config['action3']['params'][1]['default']);

		// actionFoo()
		$this->assertArrayHasKey('actionFoo', $_config);
		$this->assertEquals('name', $_config['actionFoo']['params'][1]['name']);
		$this->assertNull($_config['actionFoo']['params'][1]['class']);
		$this->assertEquals('string', $_config['actionFoo']['params'][1]['type']);
		$this->assertTrue($_config['actionFoo']['params'][1]['optional']);
		$this->assertEquals('Jack', $_config['actionFoo']['params'][1]['default']);

		return $ActionController;
	}

	/**
	 * @depends testConstructor
	 * @param \test\console\controller\ActionController $ActionController
	 * @return \test\console\controller\ActionController
	 * @throws \ReflectionException
	 */
	public function testResolveActionMethod(\test\console\controller\ActionController $ActionController) {
		$RefMethod = new \ReflectionMethod(ActionController::class, 'resolveActionMethod');
		$RefMethod->setAccessible(true);
		$_SERVER['SCRIPT_FILENAME'] = 'sys.php';

		$_SERVER['argv'] = ['sys', 'mod1'];
		$Req             = new Request();
		$Req->setAttribute('APP_MOD_CONTROLLER_URI', '');
		$this->assertEquals('index', $RefMethod->invoke($ActionController, $Req));

		$_SERVER['argv'] = ['sys', 'mod1', 'foo'];
		$Req             = new Request();
		$Req->setAttribute('APP_MOD_CONTROLLER_URI', 'foo');
		$this->assertEquals('foo', $RefMethod->invoke($ActionController, $Req));

		$_SERVER['argv'] = ['sys', 'mod1', 'action-foo'];
		$Req             = new Request();
		$Req->setAttribute('APP_MOD_CONTROLLER_URI', 'action-foo');
		$this->assertEquals('actionFoo', $RefMethod->invoke($ActionController, $Req));

		$_SERVER['argv'] = ['sys', 'mod1', 'not-exists'];
		$Req             = new Request();
		$Req->setAttribute('APP_MOD_CONTROLLER_URI', 'not-exists');
		$this->assertEquals('fallback', $RefMethod->invoke($ActionController, $Req));

		return $ActionController;
	}

	/**
	 * @depends testResolveActionMethod
	 * @throws \ReflectionException
	 */
	public function testResolveActionException() {
		$this->expectExceptionCode(111);
		$this->expectException(\renovant\core\console\Exception::class);
		$ActionController2 = new \test\console\controller\ActionController2();
		$RefMethod         = new \ReflectionMethod(ActionController::class, 'resolveActionMethod');
		$RefMethod->setAccessible(true);
		$_SERVER['SCRIPT_FILENAME'] = 'sys.php';

		$_SERVER['argv'] = ['sys', 'mod1', 'not-exists'];
		$Req             = new Request();
		$Req->setAttribute('APP_MOD_CONTROLLER_URI', 'not-exists');
		$RefMethod->invoke($ActionController2, $Req);
	}

	/**
	 * @depends testResolveActionMethod
	 * @param \test\console\controller\ActionController $ActionController
	 * @throws \renovant\core\console\Exception
	 */
	public function testHandle(\test\console\controller\ActionController $ActionController) {
		$_SERVER['argv'] = ['sys', 'mod1', 'action2', '--id=7'];
		$Req             = new Request();
		$Req->setAttribute('APP_MOD_CONTROLLER_URI', 'action2');
		$Res = new Response();
		$ActionController->handle($Req, $Res);
		$this->assertEquals('id-7', $Res->getView());
		$this->assertEquals(7, $Res->get('id'));

		$_SERVER['argv'] = ['sys', 'mod1', 'action3'];
		$Req             = new Request();
		$Req->setAttribute('APP_MOD_CONTROLLER_URI', 'action3');
		$Res = new Response();
		$ActionController->handle($Req, $Res);
		$this->assertEquals('view3', $Res->getView());
		$this->assertEquals('Tom', $Res->get('name'));

		$_SERVER['argv'] = ['sys', 'mod1', 'action3', '--name=Jack'];
		$_GET['name']    = 'Jack';
		$Req             = new Request();
		$Req->setAttribute('APP_MOD_CONTROLLER_URI', 'action3');
		$Res = new Response();
		$ActionController->handle($Req, $Res);
		$this->assertEquals('view3', $Res->getView());
		$this->assertEquals('Jack', $Res->get('name'));

		$_SERVER['argv'] = ['sys', 'mod1', 'action-foo', '--name=Rob'];
		$_GET['name']    = 'Jack';
		$Req             = new Request();
		$Req->setAttribute('APP_MOD_CONTROLLER_URI', 'action-foo');
		$Res = new Response();
		$ActionController->handle($Req, $Res);
		$this->assertEquals('action-foo', $Res->getView());
		$this->assertEquals('Rob', $Res->get('name'));
	}
}
