<?php
namespace test\http;
use const metadigit\core\http\ENGINE_PHP;
use metadigit\core\sys,
	metadigit\core\http\Request,
	metadigit\core\http\Response,
	metadigit\core\http\Dispatcher,
	metadigit\core\http\Event;

class DispatcherTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @return Dispatcher
	 * @throws \metadigit\core\container\ContainerException
	 * @throws \ReflectionException
	 */
	function testConstruct() {
		$_SERVER['REQUEST_URI'] = '/';
		define('metadigit\core\APP_URI', '/');
		new Request;
		new Response;
		/** @var Dispatcher $Dispatcher */
		$Dispatcher = sys::context()->container()->get('test.http.Dispatcher');

		$RefProp = new \ReflectionProperty(Dispatcher::class, 'routes');
		$RefProp->setAccessible(true);
		$routes = $RefProp->getValue($Dispatcher);
		$this->assertCount(4, $routes);
		$this->assertArrayHasKey('/catalog/', $routes);
		$this->assertEquals('test.http.AbstractController', $routes['/catalog/']);

		return $Dispatcher;
	}

	/**
	 * @depends testConstruct
	 * @param Dispatcher $Dispatcher
	 * @return Dispatcher
	 * @throws \ReflectionException
	 */
	function testDoRoute(Dispatcher $Dispatcher) {
		$RefMethod = new \ReflectionMethod(Dispatcher::class, 'doRoute');
		$RefMethod->setAccessible(true);

		$_SERVER['REQUEST_URI'] = '/home';
		$Req = new Request;
		$Req->setAttribute('APP_URI', '/home');
		$this->assertSame('test.http.SimpleController', $RefMethod->invoke($Dispatcher, $Req));
		$this->assertSame('', $Req->getAttribute('APP_CONTROLLER_URI'));

		$_SERVER['REQUEST_URI'] = '/mod1/foo';
		$Req = new Request;
		$Req->setAttribute('APP_URI', '/mod1/foo');
		$this->assertSame('test.http.ActionController', $RefMethod->invoke($Dispatcher, $Req));
		$this->assertSame('foo', $Req->getAttribute('APP_CONTROLLER_URI'));

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['REQUEST_URI'] = '/rest/book/14';
		$Req = new Request;
		$Req->setAttribute('APP_URI', '/rest/book/14');
		$this->assertSame('test.http.RestActionController', $RefMethod->invoke($Dispatcher, $Req));
		$this->assertSame('book/14', $Req->getAttribute('APP_CONTROLLER_URI'));

		$_SERVER['REQUEST_URI'] = '/catalog/books/science/13';
		$Req = new Request;
		$Req->setAttribute('APP_URI', '/catalog/books/science/13');
		$this->assertSame('test.http.AbstractController', $RefMethod->invoke($Dispatcher, $Req));
		$this->assertSame('books/science/13', $Req->getAttribute('APP_CONTROLLER_URI'));

		return $Dispatcher;
	}

	/**
	 * @depends testConstruct
	 * @expectedException \metadigit\core\http\Exception
	 * @expectedExceptionCode 11
	 * @param Dispatcher $Dispatcher
	 * @throws \ReflectionException
	 */
	function testDoRouteException11(Dispatcher $Dispatcher) {
		$RefMethod = new \ReflectionMethod(Dispatcher::class, 'doRoute');
		$RefMethod->setAccessible(true);

		$_SERVER['REQUEST_URI'] = '/not-exists/foo';
		$Req = new Request;
		$Req->setAttribute('APP_URI', '/not-exists/foo');
		$RefMethod->invoke($Dispatcher, $Req);
	}

	/**
	 * @depends testConstruct
	 * @param Dispatcher $Dispatcher
	 * @throws \ReflectionException
	 */
	function testResolveView(Dispatcher $Dispatcher) {
		$RefMethod = new \ReflectionMethod(Dispatcher::class, 'resolveView');
		$RefMethod->setAccessible(true);

		$_SERVER['REQUEST_URI'] = '/';
		$Req = new Request;
		$Req->setAttribute('APP_URI', '/');
		$Req->setAttribute('APP_DIR', TEST_DIR.'/http/');
		$Res = (new Response)->setView('index', null, ENGINE_PHP);
		list($View, $resource) = $RefMethod->invoke($Dispatcher, $Req, $Res, new Event($Req, $Res));
		$this->assertInstanceOf('metadigit\core\http\view\PhpView', $View);
		$this->assertSame('/index', $resource);
		$Res = (new Response)->setView('/index', null, ENGINE_PHP);
		list($View, $resource) = $RefMethod->invoke($Dispatcher, $Req, $Res, new Event($Req, $Res));
		$this->assertInstanceOf('metadigit\core\http\view\PhpView', $View);
		$this->assertSame('/index', $resource);

		$_SERVER['REQUEST_URI'] = '/app/mod1/';
		$Req = new Request;
		$Req->setAttribute('APP_URI', '/mod1/');
		$Req->setAttribute('APP_DIR', TEST_DIR.'/http/');
		$Res = (new Response)->setView('index', null, ENGINE_PHP);
		list($View, $resource) = $RefMethod->invoke($Dispatcher, $Req, $Res, new Event($Req, $Res));
		$this->assertInstanceOf('metadigit\core\http\view\PhpView', $View);
		$this->assertSame('/mod1/index', $resource);
		$Res = (new Response)->setView('/mod1/index', null, ENGINE_PHP);
		list($View, $resource) = $RefMethod->invoke($Dispatcher, $Req, $Res, new Event($Req, $Res));
		$this->assertInstanceOf('metadigit\core\http\view\PhpView', $View);
		$this->assertSame('/mod1/index', $resource);


		$_SERVER['REQUEST_URI'] = '/app/mod1/foo1';
		$Req = new Request;
		$Req->setAttribute('APP_URI', '/mod1/foo1');
		$Req->setAttribute('APP_DIR', TEST_DIR.'/http/');
		$Res = (new Response)->setView('foo1', null, ENGINE_PHP);
		list($View, $resource) = $RefMethod->invoke($Dispatcher, $Req, $Res, new Event($Req, $Res));
		$this->assertInstanceOf('metadigit\core\http\view\PhpView', $View);
		$this->assertSame('/mod1/foo1', $resource);
		$Res = (new Response)->setView('/mod1/foo1', null, ENGINE_PHP);
		list($View, $resource) = $RefMethod->invoke($Dispatcher, $Req, $Res, new Event($Req, $Res));
		$this->assertInstanceOf('metadigit\core\http\view\PhpView', $View);
		$this->assertSame('/mod1/foo1', $resource);
	}

	/**
	 * @depends               testConstruct
	 * @expectedException \metadigit\core\http\Exception
	 * @expectedExceptionCode 12
	 * @param Dispatcher $Dispatcher
	 * @throws \ReflectionException
	 */
	function testResolveViewException(Dispatcher $Dispatcher) {
		$RefMethod = new \ReflectionMethod(Dispatcher::class, 'resolveView');
		$RefMethod->setAccessible(true);

		$_SERVER['REQUEST_URI'] = '/';
		$Req = new Request;
		$Req->setAttribute('APP_URI', '/');
		$Req->setAttribute('APP_DIR', TEST_DIR.'/http/');
		$Res = new Response;
		$Res->setView('index', null, 'xxx');
		$RefMethod->invoke($Dispatcher, $Req, $Res, new Event($Req, $Res));
	}

	/**
	 * @depends testConstruct
	 * @throws \metadigit\core\container\ContainerException
	 */
	function testDispatch() {
		ob_start();
		sys::cache('sys')->delete('test.http.Dispatcher');
		$Dispatcher = sys::context()->container()->get('test.http.Dispatcher');
		$_SERVER['REQUEST_URI'] = '/';
		define('SESSION_UID', 1);
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('APP_URI', '/home');
		$Req->setAttribute('APP_DIR', TEST_DIR.'/http/');
		$this->assertNull($Dispatcher->dispatch($Req, $Res));
		$output = ob_get_clean();
		$this->assertRegExp('/<title>index<\/title>/', $output);
	}
}
