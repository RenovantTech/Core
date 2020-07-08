<?php
namespace test\auth;
use renovant\core\sys,
	renovant\core\auth\Auth;

class AuthTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @return Auth
	 * @throws \ReflectionException
	 * @throws \renovant\core\context\ContextException
	 * @throws \renovant\core\event\EventDispatcherException
	 */
	function testConstruct(): Auth {
		/** @var @AuthService $AuthService */
		$AuthService = sys::context()->get('sys.AUTH');
		$Auth = $AuthService->authenticate(11, 11, 'John Black', 'admin', [
			'foo' => 'foo1',
			'bar' => 'bar1'
		]);
		$this->assertInstanceOf(Auth::class, $Auth);
		return $Auth;
	}

	/**
	 * @depends testConstruct
	 */
	function testInstance() {
		$this->assertInstanceOf(Auth::class, Auth::instance());
	}

	/**
	 * @depends testConstruct
	 * @param Auth $Auth
	 */
	function testData(Auth $Auth) {
		$this->assertEquals('foo1', $Auth->data('foo'));
		$this->assertEquals('bar1', $Auth->data('bar'));
		$this->assertEquals(['foo'=>'foo1', 'bar'=>'bar1'], $Auth->data());
	}

	/**
	 * @depends testConstruct
	 * @param Auth $Auth
	 */
	function testGID(Auth $Auth) {
		$this->assertEquals(11, $Auth->GID());
	}

	/**
	 * @depends testConstruct
	 * @param Auth $Auth
	 */
	function testGROUP(Auth $Auth) {
		$this->assertEquals('admin', $Auth->GROUP());
	}

	/**
	 * @depends testConstruct
	 * @param Auth $Auth
	 */
	function testNAME(Auth $Auth) {
		$this->assertEquals('John Black', $Auth->NAME());
	}

	/**
	 * @depends testConstruct
	 * @param Auth $Auth
	 */
	function testUID(Auth $Auth) {
		$this->assertEquals(11, $Auth->UID());
	}
}
