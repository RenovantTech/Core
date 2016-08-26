<?php
namespace test\console;
use metadigit\core\Kernel,
	metadigit\core\console\Dispatcher,
	metadigit\core\context\Context,
	metadigit\core\cli\Request,
	metadigit\core\cli\Response;

class DispatcherTest extends \PHPUnit_Framework_TestCase {

	function testConstruct() {
		$_SERVER['REQUEST_URI'] = '/';
		define('metadigit\core\APP_URI', '/');
		$Req = new Request;
		$Res = new Response;
		$Dispatcher = Context::factory('mock.console')->get('mock.console.Dispatcher');
		$this->assertInstanceOf('metadigit\core\console\Dispatcher', $Dispatcher);

		$ReflProp = new \ReflectionProperty('metadigit\core\console\Dispatcher', 'Context');
		$ReflProp->setAccessible(true);
		$Context = $ReflProp->getValue($Dispatcher);
		$this->assertInstanceOf('metadigit\core\context\Context', $Context);

		return $Dispatcher;
	}

	/**
	 * @depends testConstruct
	 */
	function testResolveController(Dispatcher $Dispatcher) {
		$ReflMethod = new \ReflectionMethod('metadigit\core\console\Dispatcher', 'resolveController');
		$ReflMethod->setAccessible(true);

		$_SERVER['argv'] = ['console','db','optimize'];
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('APP_URI', 'db optimize');
		$this->assertInstanceOf('mock\console\controller\AbstractController', $ReflMethod->invoke($Dispatcher, $Req, $Res));

		$_SERVER['argv'] = ['console','mod1','foo'];
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('APP_URI', 'mod1 foo');
		$this->assertInstanceOf('mock\console\controller\ActionController', $ReflMethod->invoke($Dispatcher, $Req, $Res));

		$_SERVER['argv'] = ['console','cron','backup'];
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('APP_URI', 'cron backup');
		$this->assertInstanceOf('mock\console\controller\SimpleController', $ReflMethod->invoke($Dispatcher, $Req, $Res));

		return $Dispatcher;
	}

	/**
	 * @depends testConstruct
	 */
	function testResolveView(Dispatcher $Dispatcher) {
		$ReflMethod = new \ReflectionMethod('metadigit\core\console\Dispatcher', 'resolveView');
		$ReflMethod->setAccessible(true);

		$_SERVER['argv'] = ['sys','mod1','index'];
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('APP_URI', 'mod1 index');
		$Req->setAttribute('APP_DIR', MOCK_DIR.'/console/');
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
		$Req->setAttribute('APP_DIR', MOCK_DIR.'/console/');
		list($View, $resource) = $ReflMethod->invoke($Dispatcher, 'foo1', $Req, $Res);
		$this->assertInstanceOf('metadigit\core\console\view\PhpView', $View);
		$this->assertSame('/mod1/foo1', $resource);
		list($View, $resource) = $ReflMethod->invoke($Dispatcher, '/mod1/foo1', $Req, $Res);
		$this->assertInstanceOf('metadigit\core\console\view\PhpView', $View);
		$this->assertSame('/mod1/foo1', $resource);
	}

	/**
	 * @depends testConstruct
	 * @expectedException \metadigit\core\console\Exception
	 * @expectedExceptionCode 12
	 */
	function testResolveViewException(Dispatcher $Dispatcher) {
		$ReflMethod = new \ReflectionMethod('metadigit\core\console\Dispatcher', 'resolveView');
		$ReflMethod->setAccessible(true);

		$_SERVER['argv'] = ['sys','mod1','indexERR'];
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('APP_URI', 'mod1 index');
		$Req->setAttribute('APP_DIR', MOCK_DIR.'/console/');
		$ReflMethod->invoke($Dispatcher, 'xxx:index', $Req, $Res);
	}

	/**
	 * @depends testConstruct
	 */
	function testDispatch() {
		$this->expectOutputRegex('/<title>mod1\/index<\/title>/');
		Kernel::cache('kernel')->delete('mock.console.Dispatcher');
		$Dispatcher = Context::factory('mock.console',false)->get('mock.console.Dispatcher');
		$_SERVER['argv'] = ['sys','mod1','index'];
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('APP_URI', 'mod1 index');
		$Req->setAttribute('APP_DIR', MOCK_DIR.'/console/');
		$this->assertNull($Dispatcher->dispatch($Req, $Res));
	}
}
