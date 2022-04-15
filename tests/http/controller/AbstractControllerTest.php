<?php
namespace test\http\controller;
use renovant\core\http\Request,
	renovant\core\http\Response;

class AbstractControllerTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @return AbstractController
	 */
	function testConstructor() {
		$AbstractController = new AbstractController;
		$this->assertInstanceOf('renovant\core\http\ControllerInterface', $AbstractController);
		$this->assertInstanceOf('renovant\core\http\controller\AbstractController', $AbstractController);

		$RefProp = new \ReflectionProperty('renovant\core\http\controller\AbstractController', '_config');
		$RefProp->setAccessible(true);
		$_config = $RefProp->getValue($AbstractController);

		$this->assertEquals('Req', $_config['params'][0]['name']);
		$this->assertEquals(Request::class, $_config['params'][0]['class']);
		$this->assertNull($_config['params'][0]['type']);
		$this->assertFalse($_config['params'][0]['optional']);

		$this->assertEquals('categ', $_config['params'][2]['name']);
		$this->assertNull($_config['params'][2]['class']);
		$this->assertEquals('string', $_config['params'][2]['type']);
		$this->assertFalse($_config['params'][2]['optional']);

		$this->assertEquals('id', $_config['params'][4]['name']);
		$this->assertNull($_config['params'][4]['class']);
		$this->assertEquals('int', $_config['params'][4]['type']);
		$this->assertTrue($_config['params'][4]['optional']);
		$this->assertEquals(1, $_config['params'][4]['default']);

		return $AbstractController;
	}

	/**
	 * @depends testConstructor
	 * @param \test\http\controller\AbstractController $AbstractController
	 */
	function testHandle(AbstractController $AbstractController) {
		$_SERVER['REQUEST_URI'] = '/catalog/books/history+math/32';
		$_GET['name'] = 'Jack';
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('APP_MOD_CONTROLLER_URI', 'books/history+math/32');
		$AbstractController->handle($Req, $Res);
		$this->assertEquals(['view',null,null], $Res->getView());
		$this->assertEquals('books', $Res->get('categ'));
		$this->assertEquals('history+math', $Res->get('tags'));
		$this->assertSame(32, $Res->get('id'));
	}
}
