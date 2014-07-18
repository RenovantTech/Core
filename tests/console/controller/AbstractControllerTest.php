<?php
namespace test\console\controller;
use metadigit\core\cli\Request,
	metadigit\core\cli\Response,
	metadigit\core\console\controller\AbstractController;

class AbstractControllerTest extends \PHPUnit_Framework_TestCase {

	function testConstructor() {
		$AbstractController = new \mock\console\controller\AbstractController;
		$this->assertInstanceOf('metadigit\core\console\ControllerInterface', $AbstractController);
		$this->assertInstanceOf('metadigit\core\console\controller\AbstractController', $AbstractController);

		$ReflProp = new \ReflectionProperty('metadigit\core\console\controller\AbstractController', '_handle');
		$ReflProp->setAccessible(true);
		$_handle = $ReflProp->getValue($AbstractController);

		$this->assertEquals('name', $_handle['params'][2]['name']);
		$this->assertNull($_handle['params'][2]['class']);
		$this->assertEquals('string', $_handle['params'][2]['type']);
		$this->assertTrue($_handle['params'][2]['optional']);
		$this->assertEquals('Tom', $_handle['params'][2]['default']);

		return $AbstractController;
	}

	/**
	 * @depends testConstructor
	 */
	function testHandle(\mock\console\controller\AbstractController $AbstractController) {
		$_SERVER['argv'] = ['sys','db','--name=Jack'];
		$Req = new Request;
		$Res = new Response;
		$AbstractController->handle($Req, $Res);
		$this->assertEquals('view', $Res->getView());
		$this->assertEquals('Jack', $Res->get('name'));
	}
}