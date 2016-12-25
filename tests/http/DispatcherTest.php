<?php
namespace test\http;
use function metadigit\core\cache;
use metadigit\core\context\Context,
	metadigit\core\http\Request,
	metadigit\core\http\Response,
	metadigit\core\http\Dispatcher;

class DispatcherTest extends \PHPUnit_Framework_TestCase {

	function testConstruct() {
		$_SERVER['REQUEST_URI'] = '/';
		define('metadigit\core\APP_URI', '/');
		new Request;
		new Response;
		$Dispatcher = Context::factory('mock.http')->getContainer()->get('mock.http.Dispatcher');

		$ReflProp = new \ReflectionProperty('metadigit\core\http\Dispatcher', 'routes');
		$ReflProp->setAccessible(true);
		$routes = $ReflProp->getValue($Dispatcher);
		$this->assertCount(4, $routes);
		$this->assertArrayHasKey('/catalog/*', $routes);
		$this->assertEquals('mock.http.AbstractController', $routes['/catalog/*']);

		return $Dispatcher;
	}

	/**
	 * @depends testConstruct
	 * @param Dispatcher $Dispatcher
	 * @return Dispatcher
	 */
	function testResolveController(Dispatcher $Dispatcher) {
		$ReflMethod = new \ReflectionMethod('metadigit\core\http\Dispatcher', 'resolveController');
		$ReflMethod->setAccessible(true);

		$_SERVER['REQUEST_URI'] = '/home';
		$Req = new Request;
		$Req->setAttribute('APP_URI', '/home');
		$this->assertSame('mock.http.SimpleController', $ReflMethod->invoke($Dispatcher, $Req));

		$_SERVER['REQUEST_URI'] = '/mod1/foo';
		$Req = new Request;
		$Req->setAttribute('APP_URI', '/mod1/foo');
		$this->assertSame('mock.http.ActionController', $ReflMethod->invoke($Dispatcher, $Req));

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['REQUEST_URI'] = '/rest/book/14';
		$Req = new Request;
		$Req->setAttribute('APP_URI', '/rest/book/14');
		$this->assertSame('mock.http.RestActionController', $ReflMethod->invoke($Dispatcher, $Req));

		$_SERVER['REQUEST_URI'] = '/catalog/books/science/13';
		$Req = new Request;
		$Req->setAttribute('APP_URI', '/catalog/books/science/13');
		$this->assertSame('mock.http.AbstractController', $ReflMethod->invoke($Dispatcher, $Req));

		return $Dispatcher;
	}

	/**
	 * @depends               testConstruct
	 * @expectedException \metadigit\core\http\Exception
	 * @expectedExceptionCode 11
	 * @param Dispatcher $Dispatcher
	 */
	function testResolveControllerException11(Dispatcher $Dispatcher) {
		$ReflMethod = new \ReflectionMethod('metadigit\core\http\Dispatcher', 'resolveController');
		$ReflMethod->setAccessible(true);

		$_SERVER['REQUEST_URI'] = '/not-exists/foo';
		$Req = new Request;
		$Req->setAttribute('APP_URI', '/not-exists/foo');
		$ReflMethod->invoke($Dispatcher, $Req);
	}

	/**
	 * @depends testConstruct
	 * @param Dispatcher $Dispatcher
	 */
	function testResolveView(Dispatcher $Dispatcher) {
		$ReflMethod = new \ReflectionMethod('metadigit\core\http\Dispatcher', 'resolveView');
		$ReflMethod->setAccessible(true);

		$_SERVER['REQUEST_URI'] = '/';
		$Req = new Request;
		$Req->setAttribute('APP_URI', '/');
		$Req->setAttribute('APP_DIR', MOCK_DIR.'/http/');
		list($View, $resource) = $ReflMethod->invoke($Dispatcher, 'index', $Req);
		$this->assertInstanceOf('metadigit\core\http\view\PhpView', $View);
		$this->assertSame('/index', $resource);
		list($View, $resource) = $ReflMethod->invoke($Dispatcher, '/index', $Req);
		$this->assertInstanceOf('metadigit\core\http\view\PhpView', $View);
		$this->assertSame('/index', $resource);

		$_SERVER['REQUEST_URI'] = '/app/mod1/';
		$Req = new Request;
		$Req->setAttribute('APP_URI', '/mod1/');
		$Req->setAttribute('APP_DIR', MOCK_DIR.'/http/');
		list($View, $resource) = $ReflMethod->invoke($Dispatcher, 'index', $Req);
		$this->assertInstanceOf('metadigit\core\http\view\PhpView', $View);
		$this->assertSame('/mod1/index', $resource);
		list($View, $resource) = $ReflMethod->invoke($Dispatcher, '/mod1/index', $Req);
		$this->assertInstanceOf('metadigit\core\http\view\PhpView', $View);
		$this->assertSame('/mod1/index', $resource);


		$_SERVER['REQUEST_URI'] = '/app/mod1/foo1';
		$Req = new Request;
		$Req->setAttribute('APP_URI', '/mod1/foo1');
		$Req->setAttribute('APP_DIR', MOCK_DIR.'/http/');
		list($View, $resource) = $ReflMethod->invoke($Dispatcher, 'foo1', $Req);
		$this->assertInstanceOf('metadigit\core\http\view\PhpView', $View);
		$this->assertSame('/mod1/foo1', $resource);
		list($View, $resource) = $ReflMethod->invoke($Dispatcher, '/mod1/foo1', $Req);
		$this->assertInstanceOf('metadigit\core\http\view\PhpView', $View);
		$this->assertSame('/mod1/foo1', $resource);
	}

	/**
	 * @depends               testConstruct
	 * @expectedException \metadigit\core\http\Exception
	 * @expectedExceptionCode 12
	 * @param Dispatcher $Dispatcher
	 */
	function testResolveViewException(Dispatcher $Dispatcher) {
		$ReflMethod = new \ReflectionMethod('metadigit\core\http\Dispatcher', 'resolveView');
		$ReflMethod->setAccessible(true);

		$_SERVER['REQUEST_URI'] = '/';
		$Req = new Request;
		$Req->setAttribute('APP_URI', '/');
		$Req->setAttribute('APP_DIR', MOCK_DIR.'/http/');
		$ReflMethod->invoke($Dispatcher, 'xxx:index', $Req);
	}

	/**
	 * @depends testConstruct
	 */
	function testDispatch() {
		$this->expectOutputRegex('/<title>index<\/title>/');
		cache('kernel')->delete('mock.http.Dispatcher');
		$Dispatcher = Context::factory('mock.http',false)->get('mock.http.Dispatcher');
		$_SERVER['REQUEST_URI'] = '/';
		define('SESSION_UID', 1);
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('APP_URI', '/home');
		$Req->setAttribute('APP_DIR', MOCK_DIR.'/http/');
		$this->assertNull($Dispatcher->dispatch($Req, $Res));
	}
}
