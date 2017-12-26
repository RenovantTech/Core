<?php
namespace test\console;
use metadigit\core\sys,
	metadigit\core\console\Dispatcher,
	metadigit\core\context\Context,
	metadigit\core\cli\Request,
	metadigit\core\cli\Response;

class DispatcherTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @return Dispatcher
	 * @throws \metadigit\core\container\ContainerException
	 * @throws \metadigit\core\util\yaml\YamlException
	 */
	function testConstruct() {
		$_SERVER['REQUEST_URI'] = '/';
		define('metadigit\core\APP_URI', '/');
		new Request;
		new Response;
		$Dispatcher = sys::context()->container()->get('test.console.Dispatcher');
		$this->assertInstanceOf(Dispatcher::class, $Dispatcher);
		return $Dispatcher;
	}

	/**
	 * @depends testConstruct
	 * @param Dispatcher $Dispatcher
	 * @return Dispatcher
	 */
	function testResolveController(Dispatcher $Dispatcher) {
		$ReflMethod = new \ReflectionMethod('metadigit\core\console\Dispatcher', 'resolveController');
		$ReflMethod->setAccessible(true);

		$_SERVER['argv'] = ['console','db','optimize'];
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('APP_URI', 'db optimize');
		$this->assertSame('test.console.AbstractController', $ReflMethod->invoke($Dispatcher, $Req, $Res));

		$_SERVER['argv'] = ['console','mod1','foo'];
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('APP_URI', 'mod1 foo');
		$this->assertSame('test.console.ActionController', $ReflMethod->invoke($Dispatcher, $Req, $Res));

		$_SERVER['argv'] = ['console','cron','backup'];
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('APP_URI', 'cron backup');
		$this->assertSame('test.console.SimpleController', $ReflMethod->invoke($Dispatcher, $Req, $Res));

		return $Dispatcher;
	}

	/**
	 * @depends testConstruct
	 * @param Dispatcher $Dispatcher
	 */
	function testResolveView(Dispatcher $Dispatcher) {
		$ReflMethod = new \ReflectionMethod('metadigit\core\console\Dispatcher', 'resolveView');
		$ReflMethod->setAccessible(true);

		$_SERVER['argv'] = ['sys','mod1','index'];
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('APP_URI', 'mod1 index');
		$Req->setAttribute('APP_DIR', TEST_DIR.'/console/');
		list($View, $resource) = $ReflMethod->invoke($Dispatcher, 'index', $Req, $Res);
		$this->assertInstanceOf('metadigit\core\console\view\PhpView', $View);
		$this->assertSame('/mod1/index', $resource);
		list($View, $resource) = $ReflMethod->invoke($Dispatcher, '/mod1/index', $Req, $Res);
		$this->assertInstanceOf('metadigit\core\console\view\PhpView', $View);
		$this->assertSame('/mod1/index', $resource);

		$_SERVER['argv'] = ['sys','mod1','foo1'];
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('APP_URI', 'mod1 foo1');
		$Req->setAttribute('APP_DIR', TEST_DIR.'/console/');
		list($View, $resource) = $ReflMethod->invoke($Dispatcher, 'foo1', $Req, $Res);
		$this->assertInstanceOf('metadigit\core\console\view\PhpView', $View);
		$this->assertSame('/mod1/foo1', $resource);
		list($View, $resource) = $ReflMethod->invoke($Dispatcher, '/mod1/foo1', $Req, $Res);
		$this->assertInstanceOf('metadigit\core\console\view\PhpView', $View);
		$this->assertSame('/mod1/foo1', $resource);
	}

	/**
	 * @depends               testConstruct
	 * @expectedException \metadigit\core\console\Exception
	 * @expectedExceptionCode 12
	 * @param Dispatcher $Dispatcher
	 */
	function testResolveViewException(Dispatcher $Dispatcher) {
		$ReflMethod = new \ReflectionMethod('metadigit\core\console\Dispatcher', 'resolveView');
		$ReflMethod->setAccessible(true);

		$_SERVER['argv'] = ['sys','mod1','indexERR'];
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('APP_URI', 'mod1 index');
		$Req->setAttribute('APP_DIR', TEST_DIR.'/console/');
		$ReflMethod->invoke($Dispatcher, 'xxx:index', $Req, $Res);
	}

	/**
	 * @depends testConstruct
	 */
	function testDispatch() {
		$this->expectOutputRegex('/<title>mod1\/index<\/title>/');
		sys::cache('sys')->delete('test.console.Dispatcher');
		$Dispatcher = sys::context()->container()->get('test.console.Dispatcher');
		$_SERVER['argv'] = ['sys','mod1','index'];
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('APP_URI', 'mod1 index');
		$Req->setAttribute('APP_DIR', TEST_DIR.'/console/');
		$this->assertNull($Dispatcher->dispatch($Req, $Res));
	}
}
