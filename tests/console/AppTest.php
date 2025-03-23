<?php
namespace test\console;

use renovant\core\sys;
use renovant\core\console\{App, Request, Response};
use renovant\core\context\ContextException,
renovant\core\event\EventDispatcherException;

class AppTest extends \PHPUnit\Framework\TestCase {
	/**
	 * @throws ContextException
	 * @throws EventDispatcherException
	 * @throws \ReflectionException
	 */
	public function testRun() {
		$App = sys::context()->get('test.console.AppCLI');
		//		$this->assertInstanceOf(App::class, $App);

		$_SERVER['SCRIPT_FILENAME'] = __DIR__ . DIRECTORY_SEPARATOR . 'console';
		$_SERVER['argv']            = [
			0 => __DIR__ . DIRECTORY_SEPARATOR . 'console',
			1 => 'mod1',
			2 => 'foo',
			3 => '--bar=2'
		];

		$Req = new Request();
		$Res = new Response();
		$this->assertNull($App->run($Req, $Res));
		$this->assertSame('TEST-CONSOLE', $Req->getAttribute('APP'));
		$this->assertSame('MOD1', $Req->getAttribute('APP_MOD'));
		$this->assertSame('test.console', $Req->getAttribute('APP_MOD_NAMESPACE'));
		$this->assertSame('mod1 foo', $Req->getAttribute('APP_MOD_URI'));
	}
}
