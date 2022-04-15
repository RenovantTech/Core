<?php
namespace test\console\controller;
use renovant\core\console\ControllerInterface,
	renovant\core\console\Request,
	renovant\core\console\Response,
	renovant\core\console\controller\AbstractController;

class AbstractControllerTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$AbstractController = new \test\console\controller\AbstractController;
		$this->assertInstanceOf(ControllerInterface::class, $AbstractController);
		$this->assertInstanceOf(AbstractController::class, $AbstractController);

		$RefProp = new \ReflectionProperty(AbstractController::class, '_config');
		$RefProp->setAccessible(true);
		$_config = $RefProp->getValue($AbstractController);

		$this->assertEquals('Req', $_config['params'][0]['name']);
		$this->assertEquals(Request::class, $_config['params'][0]['class']);
		$this->assertNull($_config['params'][0]['type']);

		$this->assertEquals('name', $_config['params'][2]['name']);
		$this->assertNull($_config['params'][2]['class']);
		$this->assertEquals('string', $_config['params'][2]['type']);
		$this->assertTrue($_config['params'][2]['optional']);
		$this->assertEquals('Tom', $_config['params'][2]['default']);

		return $AbstractController;
	}

	/**
	 * @depends testConstructor
	 * @param \test\console\controller\AbstractController $AbstractController
	 */
	function testHandle(\test\console\controller\AbstractController $AbstractController) {
		$_SERVER['argv'] = ['sys','db','--name=Jack'];
		$Req = new Request;
		$Res = new Response;
		$AbstractController->handle($Req, $Res);
		$this->assertEquals('view', $Res->getView());
		$this->assertEquals('Jack', $Res->get('name'));
	}
}
