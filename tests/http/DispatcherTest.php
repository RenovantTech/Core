<?php
namespace test\http;
use const renovant\core\http\ENGINE_PHP;
use renovant\core\sys,
	renovant\core\acl\ACL,
	renovant\core\http\Request,
	renovant\core\http\Response,
	renovant\core\http\Dispatcher,
	renovant\core\http\Event,
	test\acl\ACLTest;

class DispatcherTest extends \PHPUnit\Framework\TestCase {

	static function setUpBeforeClass():void {
		ACLTest::setUpBeforeClass();
		new ACL(['ORM'], 'mysql');
	}

	static function tearDownAfterClass():void {
		ACLTest::tearDownAfterClass();
	}
	/**
	 * @return Dispatcher
	 * @throws \renovant\core\container\ContainerException
	 * @throws \ReflectionException
	 */
	function testConstruct() {
		$_SERVER['REQUEST_URI'] = '/';
		define('renovant\core\APP_MOD_URI', '/');
		new Request;
		new Response;
		/** @var Dispatcher $Dispatcher */
		$Dispatcher = sys::context()->container()->get('test.http.Dispatcher');

		$RefProp = new \ReflectionProperty(Dispatcher::class, 'routes');
		$RefProp->setAccessible(true);
		$routes = $RefProp->getValue($Dispatcher);
		$this->assertCount(5, $routes);
		$this->assertArrayHasKey('/\/blog\/(?<category>[^\/]+)\//', $routes);
		$this->assertEquals('test.http.ActionController', $routes['/\/blog\/(?<category>[^\/]+)\//']);
		$this->assertArrayHasKey('/\/catalog\//', $routes);
		$this->assertEquals('test.http.AbstractController', $routes['/\/catalog\//']);

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
		$Req->setAttribute('APP_MOD_URI', '/home');
		$this->assertSame('test.http.SimpleController', $RefMethod->invoke($Dispatcher, $Req));
		$this->assertSame('', $Req->getAttribute('APP_MOD_CONTROLLER_URI'));

		$_SERVER['REQUEST_URI'] = '/action/foo';
		$Req = new Request;
		$Req->setAttribute('APP_MOD_URI', '/action/foo');
		$this->assertSame('test.http.ActionController', $RefMethod->invoke($Dispatcher, $Req));
		$this->assertSame('foo', $Req->getAttribute('APP_MOD_CONTROLLER_URI'));

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['REQUEST_URI'] = '/rest/book/14';
		$Req = new Request;
		$Req->setAttribute('APP_MOD_URI', '/rest/book/14');
		$this->assertSame('test.http.RestActionController', $RefMethod->invoke($Dispatcher, $Req));
		$this->assertSame('book/14', $Req->getAttribute('APP_MOD_CONTROLLER_URI'));

		$_SERVER['REQUEST_URI'] = '/catalog/books/science/13';
		$Req = new Request;
		$Req->setAttribute('APP_MOD_URI', '/catalog/books/science/13');
		$this->assertSame('test.http.AbstractController', $RefMethod->invoke($Dispatcher, $Req));
		$this->assertSame('books/science/13', $Req->getAttribute('APP_MOD_CONTROLLER_URI'));

		$_SERVER['REQUEST_URI'] = '/blog/science/13-foobar';
		$Req = new Request;
		$Req->setAttribute('APP_MOD_URI', '/blog/science/13-foobar');
		$this->assertSame('test.http.ActionController', $RefMethod->invoke($Dispatcher, $Req));
		$this->assertSame('13-foobar', $Req->getAttribute('APP_MOD_CONTROLLER_URI'));
		$this->assertSame('science', $Req->get('category'));

		return $Dispatcher;
	}

	/**
	 * @depends testConstruct
	 * @param Dispatcher $Dispatcher
	 * @throws \ReflectionException
	 */
	function testDoRouteException11(Dispatcher $Dispatcher) {
		$this->expectExceptionCode(11);
		$this->expectException(\renovant\core\http\Exception::class);
		$RefMethod = new \ReflectionMethod(Dispatcher::class, 'doRoute');
		$RefMethod->setAccessible(true);

		$_SERVER['REQUEST_URI'] = '/not-exists/foo';
		$Req = new Request;
		$Req->setAttribute('APP_MOD_URI', '/not-exists/foo');
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
		$Req->setAttribute('APP_MOD_URI', '/');
		$Req->setAttribute('APP_MOD_DIR', TEST_DIR.'/http/');
		$Res = (new Response)->setView('index', null, ENGINE_PHP);
		list($View, $resource) = $RefMethod->invoke($Dispatcher, $Req, $Res, new Event($Req, $Res));
		$this->assertInstanceOf('renovant\core\http\view\PhpView', $View);
		$this->assertSame('/index', $resource);
		$Res = (new Response)->setView('/index', null, ENGINE_PHP);
		list($View, $resource) = $RefMethod->invoke($Dispatcher, $Req, $Res, new Event($Req, $Res));
		$this->assertInstanceOf('renovant\core\http\view\PhpView', $View);
		$this->assertSame('/index', $resource);

		$_SERVER['REQUEST_URI'] = '/app/action/';
		$Req = new Request;
		$Req->setAttribute('APP_MOD_URI', '/action/');
		$Req->setAttribute('APP_MOD_DIR', TEST_DIR.'/http/');
		$Res = (new Response)->setView('index', null, ENGINE_PHP);
		list($View, $resource) = $RefMethod->invoke($Dispatcher, $Req, $Res, new Event($Req, $Res));
		$this->assertInstanceOf('renovant\core\http\view\PhpView', $View);
		$this->assertSame('/action/index', $resource);
		$Res = (new Response)->setView('/action/index', null, ENGINE_PHP);
		list($View, $resource) = $RefMethod->invoke($Dispatcher, $Req, $Res, new Event($Req, $Res));
		$this->assertInstanceOf('renovant\core\http\view\PhpView', $View);
		$this->assertSame('/action/index', $resource);


		$_SERVER['REQUEST_URI'] = '/app/action/foo1';
		$Req = new Request;
		$Req->setAttribute('APP_MOD_URI', '/action/foo1');
		$Req->setAttribute('APP_MOD_DIR', TEST_DIR.'/http/');
		$Res = (new Response)->setView('foo1', null, ENGINE_PHP);
		list($View, $resource) = $RefMethod->invoke($Dispatcher, $Req, $Res, new Event($Req, $Res));
		$this->assertInstanceOf('renovant\core\http\view\PhpView', $View);
		$this->assertSame('/action/foo1', $resource);
		$Res = (new Response)->setView('/action/foo1', null, ENGINE_PHP);
		list($View, $resource) = $RefMethod->invoke($Dispatcher, $Req, $Res, new Event($Req, $Res));
		$this->assertInstanceOf('renovant\core\http\view\PhpView', $View);
		$this->assertSame('/action/foo1', $resource);
	}

	/**
	 * @depends               testConstruct
	 * @param Dispatcher $Dispatcher
	 * @throws \ReflectionException
	 */
	function testResolveViewException(Dispatcher $Dispatcher) {
		$this->expectExceptionCode(12);
		$this->expectException(\renovant\core\http\Exception::class);
		$RefMethod = new \ReflectionMethod(Dispatcher::class, 'resolveView');
		$RefMethod->setAccessible(true);

		$_SERVER['REQUEST_URI'] = '/';
		$Req = new Request;
		$Req->setAttribute('APP_MOD_URI', '/');
		$Req->setAttribute('APP_MOD_DIR', TEST_DIR.'/http/');
		$Res = new Response;
		$Res->setView('index', null, 'xxx');
		$RefMethod->invoke($Dispatcher, $Req, $Res, new Event($Req, $Res));
	}

	/**
	 * @depends testConstruct
	 * @throws \renovant\core\container\ContainerException|\ReflectionException
	 */
	function testDispatch() {
		ob_start();
		sys::cache('sys')->delete('test.http.Dispatcher');
		$Dispatcher = sys::context()->container()->get('test.http.Dispatcher');
		$_SERVER['REQUEST_URI'] = '/';
		define('SESSION_UID', 1);
		$Req = new Request;
		$Res = new Response;
		$Req->setAttribute('APP_MOD_URI', '/home');
		$Req->setAttribute('APP_MOD_DIR', TEST_DIR.'/http/');
		$this->assertNull($Dispatcher->dispatch($Req, $Res));
		$output = ob_get_clean();
		$this->assertMatchesRegularExpression('/<title>index<\/title>/', $output);
	}
}
