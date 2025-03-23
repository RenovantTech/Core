<?php
namespace test\http;

use renovant\core\sys;
use renovant\core\http\{App, Request, Response};
use renovant\core\context\ContextException,
renovant\core\event\EventDispatcherException;

class AppTest extends \PHPUnit\Framework\TestCase {
	/**
	 * @throws EventDispatcherException
	 * @throws ContextException|\ReflectionException
	 */
	public function testRun() {
		$App = sys::context()->get('test.http.AppHTTP');
		//		$this->assertInstanceOf(App::class, $App);

		$_SERVER['SERVER_ADDR'] = 'example.com';
		$_SERVER['SERVER_PORT'] = 443;
		$_SERVER['REQUEST_URI'] = '/api/bar/';

		$Req = new Request();
		$Res = new Response();
		$this->assertNull($App->run($Req, $Res));
		$this->assertSame('TEST-APP', $Req->getAttribute('APP'));
		$this->assertSame('MOD1', $Req->getAttribute('APP_MOD'));
		$this->assertSame('test.http', $Req->getAttribute('APP_MOD_NAMESPACE'));
		$this->assertSame('/api/bar/', $Req->getAttribute('APP_MOD_URI'));
	}
}
