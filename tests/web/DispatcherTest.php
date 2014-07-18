<?php
namespace test\web;
use metadigit\core\Kernel,
	metadigit\core\context\Context,
	metadigit\core\http\Request,
	metadigit\core\http\Response,
	metadigit\core\web\Dispatcher;

class DispatcherTest extends \PHPUnit_Framework_TestCase {

	function testConstruct() {
		$_SERVER['REQUEST_URI'] = '/';
		define('metadigit\core\APP_URI', '/');
		$Req = new Request;
		$Res = new Response;
		$Dispatcher = Context::factory('mock.web')->get('mock.web.Dispatcher');
		$this->assertInstanceOf('metadigit\core\web\Dispatcher', $Dispatcher);

		$ReflProp = new \ReflectionProperty('metadigit\core\web\Dispatcher', 'Context');
		$ReflProp->setAccessible(true);
		$Context = $ReflProp->getValue($Dispatcher);
		$this->assertInstanceOf('metadigit\core\context\Context', $Context);

		$ReflProp = new \ReflectionProperty('metadigit\core\web\Dispatcher', 'routes');
		$ReflProp->setAccessible(true);
		$routes = $ReflProp->getValue($Dispatcher);
		$this->assertCount(4, $routes);
		$this->assertArrayHasKey('/catalog/*', $routes);
		$this->assertEquals('mock.web.AbstractController', $routes['/catalog/*']);

		return $Dispatcher;
	}

	/**
	 * @depends testConstruct
	 */
	function testResolveController(Dispatcher $Dispatcher) {
		$ReflMethod = new \ReflectionMethod('metadigit\core\web\Dispatcher', 'resolveController');
		$ReflMethod->setAccessible(true);

		$_SERVER['REQUEST_URI'] = '/home';
		$Req = new Request;
		$Req->setAttribute('APP_URI', '/home');
		$this->assertInstanceOf('mock\web\controller\SimpleController', $ReflMethod->invoke($Dispatcher, $Req));

		$_SERVER['REQUEST_URI'] = '/mod1/foo';
		$Req = new Request;
		$Req->setAttribute('APP_URI', '/mod1/foo');
		$this->assertInstanceOf('mock\web\controller\ActionController', $ReflMethod->invoke($Dispatcher, $Req));

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['REQUEST_URI'] = '/rest/book/14';
		$Req = new Request;
		$Req->setAttribute('APP_URI', '/rest/book/14');
		$this->assertInstanceOf('mock\web\controller\RestActionController', $ReflMethod->invoke($Dispatcher, $Req));

		$_SERVER['REQUEST_URI'] = '/catalog/books/science/13';
		$Req = new Request;
		$Req->setAttribute('APP_URI', '/catalog/books/science/13');
		$this->assertInstanceOf('mock\web\controller\AbstractController', $ReflMethod->invoke($Dispatcher, $Req));

		return $Dispatcher;
	}

	/**
	 * @depends testConstruct
	 * @expectedException \metadigit\core\web\Exception
	 * @expectedExceptionCode 11
	 */
	function testResolveControllerException11(Dispatcher $Dispatcher) {
		$ReflMethod = new \ReflectionMethod('metadigit\core\web\Dispatcher', 'resolveController');
		$ReflMethod->setAccessible(true);

		$_SERVER['REQUEST_URI'] = '/not-exists/foo';
		$Req = new Request;
		$Req->setAttribute('APP_URI', '/not-exists/foo');
		$ReflMethod->invoke($Dispatcher, $Req);
	}

	/**
	 * @depends testConstruct
	 */
	function testResolveView(Dispatcher $Dispatcher) {
		$ReflMethod = new \ReflectionMethod('metadigit\core\web\Dispatcher', 'resolveView');
		$ReflMethod->setAccessible(true);

		$_SERVER['REQUEST_URI'] = '/';
		$Req = new Request;
		$Req->setAttribute('APP_URI', '/');
		$Req->setAttribute('APP_DIR', MOCK_DIR.'/web/');
		list($View, $resource) = $ReflMethod->invoke($Dispatcher, 'index', $Req);
		$this->assertInstanceOf('metadigit\core\web\view\PhpView', $View);
		$this->assertSame('/index', $resource);
		list($View, $resource) = $ReflMethod->invoke($Dispatcher, '/index', $Req);
		$this->assertInstanceOf('metadigit\core\web\view\PhpView', $View);
		$this->assertSame('/index', $resource);

		$_SERVER['REQUEST_URI'] = '/app/mod1/';
		$Req = new Request;
		$Req->setAttribute('APP_URI', '/mod1/');
		$Req->setAttribute('APP_DIR', MOCK_DIR.'/web/');
		list($View, $resource) = $ReflMethod->invoke($Dispatcher, 'index', $Req);
		$this->assertInstanceOf('metadigit\core\web\view\PhpView', $View);
		$this->assertSame('/mod1/index', $resource);
		list($View, $resource) = $ReflMethod->invoke($Dispatcher, '/mod1/index', $Req);
		$this->assertInstanceOf('metadigit\core\web\view\PhpView', $View);
		$this->assertSame('/mod1/index', $resource);


		$_SERVER['REQUEST_URI'] = '/app/mod1/foo1';
		$Req = new Request;
		$Req->setAttribute('APP_URI', '/mod1/foo1');
		$Req->setAttribute('APP_DIR', MOCK_DIR.'/web/');
		list($View, $resource) = $ReflMethod->invoke($Dispatcher, 'foo1', $Req);
		$this->assertInstanceOf('metadigit\core\web\view\PhpView', $View);
		$this->assertSame('/mod1/foo1', $resource);
		list($View, $resource) = $ReflMethod->invoke($Dispatcher, '/mod1/foo1', $Req);
		$this->assertInstanceOf('metadigit\core\web\view\PhpView', $View);
		$this->assertSame('/mod1/foo1', $resource);
	}

	/**
	 * @depends testConstruct
	 * @expectedException \metadigit\core\web\Exception
	 * @expectedExceptionCode 12
	 */
	function testResolveViewException(Dispatcher $Dispatcher) {
		$ReflMethod = new \ReflectionMethod('metadigit\core\web\Dispatcher', 'resolveView');
		$ReflMethod->setAccessible(true);

		$_SERVER['REQUEST_URI'] = '/';
		$Req = new Request;
		$Req->setAttribute('APP_URI', '/');
		$Req->setAttribute('APP_DIR', MOCK_DIR.'/web/');
		$ReflMethod->invoke($Dispatcher, 'xxx:index', $Req);
	}

	/**
	 * @depends testConstruct
	 */
	function testDispatch() {
		$this->expectOutputRegex('/<title>index<\/title>/');
		Kernel::getCache()->delete('mock.web.Dispatcher');
		$Dispatcher = Context::factory('mock.web',false)->get('mock.web.Dispatcher');
		$_SERVER['REQUEST_URI'] = '/';
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('APP_URI', '/home');
		$Req->setAttribute('APP_DIR', MOCK_DIR.'/web/');
		$this->assertNull($Dispatcher->dispatch($Req, $Res));
	}
}