<?php
namespace test\api\controller;
use metadigit\core\sys,
	metadigit\core\api\RestOrmController,
	metadigit\core\http\Request,
	metadigit\core\http\Response;

class RestOrmControllerTest extends \PHPUnit\Framework\TestCase {

	static function setUpBeforeClass() {
		\test\db\orm\Repository1Test::setUpBeforeClass();
	}

	static function tearDownAfterClass() {
		\test\db\orm\Repository1Test::tearDownAfterClass();
	}

	protected function setUp() {
		\test\db\orm\Repository1Test::setUp();
	}

	/**
	 * @return \test\api\RestOrmController
	 * @throws \metadigit\core\container\ContainerException
	 */
	function testConstructor() {
		/** @var \test\api\RestOrmController $RestOrmController */
		$RestOrmController = sys::context()->container()->get('test.api.RestOrmController');
		$this->assertInstanceOf('metadigit\core\http\ControllerInterface', $RestOrmController);
		$this->assertInstanceOf(RestOrmController::class, $RestOrmController);
		return $RestOrmController;
	}

	/**
	 * @depends testConstructor
	 * @param RestOrmController $RestOrmController
	 * @throws \metadigit\core\context\ContextException
	 * @throws \metadigit\core\event\EventDispatcherException
	 * @throws \metadigit\core\http\Exception
	 */
	function testCreateAction(RestOrmController $RestOrmController) {
		$Req = new Request('/users', 'POST', null, ['HTTP_CONTENT_TYPE'=>'application/json'], json_encode(['name'=>'John', 'surname'=>'Brown']));
		$Res = new Response;
		$Req->setAttribute('APP_CONTROLLER_URI', 'users');
		$RestOrmController->handle($Req, $Res);
		$this->assertEquals(9, $Res->get('data')['id']);
		$this->assertEquals('John', $Res->get('data')['name']);
	}

	/**
	 * @depends testConstructor
	 * @param RestOrmController $RestOrmController
	 * @throws \metadigit\core\context\ContextException
	 * @throws \metadigit\core\event\EventDispatcherException
	 * @throws \metadigit\core\http\Exception
	 */
	function testDeleteAction(RestOrmController $RestOrmController) {
		$Req = new Request('/users/1', 'DELETE', null, ['HTTP_CONTENT_TYPE'=>'application/json']);
		$Res = new Response;
		$Req->setAttribute('APP_CONTROLLER_URI', 'users/1');
		$RestOrmController->handle($Req, $Res);
		$this->assertEquals('Albert', $Res->get('data')['name']);
	}

	/**
	 * @depends testConstructor
	 * @param RestOrmController $RestOrmController
	 * @throws \metadigit\core\context\ContextException
	 * @throws \metadigit\core\event\EventDispatcherException
	 * @throws \metadigit\core\http\Exception
	 */
	function testReadAllAction(RestOrmController $RestOrmController) {
		$Req = new Request('/users', 'GET', null, ['HTTP_CONTENT_TYPE'=>'application/json']);
		$Res = new Response;
		$Req->setAttribute('APP_CONTROLLER_URI', 'users');
		$RestOrmController->handle($Req, $Res);
		$this->assertCount(8, $Res->get('data'));
		$this->assertEquals('Albert', $Res->get('data')[0]['name']);

		// criteriaExp
		$Req = new Request('/users', 'GET', ['criteriaExp'=>'age,LTE,18'], ['HTTP_CONTENT_TYPE'=>'application/json']);
		$Res = new Response;
		$Req->setAttribute('APP_CONTROLLER_URI', 'users');
		$RestOrmController->handle($Req, $Res);
		$this->assertCount(2, $Res->get('data'));
		$this->assertEquals('Don', $Res->get('data')[0]['name']);
	}

	/**
	 * @depends testConstructor
	 * @param RestOrmController $RestOrmController
	 * @throws \metadigit\core\context\ContextException
	 * @throws \metadigit\core\event\EventDispatcherException
	 * @throws \metadigit\core\http\Exception
	 */
	function testReadAction(RestOrmController $RestOrmController) {
		$Req = new Request('/users/1', 'GET', null, ['HTTP_CONTENT_TYPE'=>'application/json']);
		$Res = new Response;
		$Req->setAttribute('APP_CONTROLLER_URI', 'users/1');
		$RestOrmController->handle($Req, $Res);
		$this->assertEquals('Albert', $Res->get('data')['name']);

		$Req = new Request('/users/5', 'GET', null, ['HTTP_CONTENT_TYPE'=>'application/json']);
		$Res = new Response;
		$Req->setAttribute('APP_CONTROLLER_URI', 'users/5');
		$RestOrmController->handle($Req, $Res);
		$this->assertEquals('Emily', $Res->get('data')['name']);
	}

	/**
	 * @depends testConstructor
	 * @param RestOrmController $RestOrmController
	 * @throws \metadigit\core\context\ContextException
	 * @throws \metadigit\core\event\EventDispatcherException
	 * @throws \metadigit\core\http\Exception
	 */
	function testUpdateAction(RestOrmController $RestOrmController) {
		$Req = new Request('/users/1', 'PUT', null, ['HTTP_CONTENT_TYPE'=>'application/json'], json_encode(['name'=>'John', 'surname'=>'Brown']));
		$Res = new Response;
		$Req->setAttribute('APP_CONTROLLER_URI', 'users/1');
		$RestOrmController->handle($Req, $Res);
		$this->assertEquals(1, $Res->get('data')['id']);
		$this->assertEquals('John', $Res->get('data')['name']);
	}
}
