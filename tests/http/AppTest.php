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
		$Res = new Response();
		//		$this->assertInstanceOf(App::class, $App);

		$_SERVER['SERVER_ADDR'] = 'example.com';
		$_SERVER['SERVER_PORT'] = 443;
		$_SERVER['REQUEST_URI'] = '/api/mod2/foo/bar';
		$Req                    = new Request();
		$this->assertNull($App->run($Req, $Res));
		$this->assertSame('TEST-APP', $Req->getAttribute('APP'));
		$this->assertSame('MOD2', $Req->getAttribute('APP_MOD'));
		$this->assertSame('test.http.mod2', $Req->getAttribute('APP_MOD_CONTEXT'));
		$this->assertSame('/foo/bar', $Req->getAttribute('APP_MOD_URI'));

		$_SERVER['SERVER_ADDR'] = 'example.com';
		$_SERVER['SERVER_PORT'] = 443;
		$_SERVER['REQUEST_URI'] = '/api/mod3/foo/bar';
		$Req                    = new Request();
		$this->assertNull($App->run($Req, $Res));
		$this->assertSame('TEST-APP', $Req->getAttribute('APP'));
		$this->assertSame('MOD3', $Req->getAttribute('APP_MOD'));
		$this->assertSame('test.http', $Req->getAttribute('APP_MOD_CONTEXT'));
		$this->assertSame('/foo/bar', $Req->getAttribute('APP_MOD_URI'));

		$_SERVER['SERVER_ADDR'] = 'example.com';
		$_SERVER['SERVER_PORT'] = 443;
		$_SERVER['REQUEST_URI'] = '/api/bar/';
		$Req                    = new Request();
		$this->assertNull($App->run($Req, $Res));
		$this->assertSame('TEST-APP', $Req->getAttribute('APP'));
		$this->assertSame('', $Req->getAttribute('APP_MOD'));
		$this->assertSame('test.http', $Req->getAttribute('APP_MOD_CONTEXT'));
		$this->assertSame('/bar/', $Req->getAttribute('APP_MOD_URI'));
	}
}
