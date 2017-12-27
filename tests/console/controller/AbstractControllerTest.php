<?php
namespace test\console\controller;
use metadigit\core\console\ControllerInterface,
	metadigit\core\console\Request,
	metadigit\core\console\Response,
	metadigit\core\console\controller\AbstractController;

class AbstractControllerTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$AbstractController = new \test\console\controller\AbstractController;
		$this->assertInstanceOf(ControllerInterface::class, $AbstractController);
		$this->assertInstanceOf(AbstractController::class, $AbstractController);

		$RefProp = new \ReflectionProperty(AbstractController::class, '_config');
		$RefProp->setAccessible(true);
		$_config = $RefProp->getValue($AbstractController);

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
