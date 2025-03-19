<?php
namespace test\auth;

use renovant\core\sys,
renovant\core\auth\Auth,
renovant\core\auth\AuthService;

class AuthTest extends \PHPUnit\Framework\TestCase {
	/**
	 * @return Auth
	 * @throws \ReflectionException
	 * @throws \renovant\core\context\ContextException
	 * @throws \renovant\core\event\EventDispatcherException
	 */
	public function testConstruct(): Auth {
		/** @var AuthService $AuthService */
		$AuthService = sys::context()->get('sys.AUTH');
		$Auth        = $AuthService->authenticate(11, 11, 'John Black', 'admin', [
			'foo' => 'foo1',
			'bar' => 'bar1'
		]);
		$this->assertInstanceOf(Auth::class, $Auth);
		return $Auth;
	}

	/**
	 * @depends testConstruct
	 */
	public function testInstance() {
		$this->assertInstanceOf(Auth::class, Auth::instance());
	}

	/**
	 * @depends testConstruct
	 * @param Auth $Auth
	 */
	public function testData(Auth $Auth) {
		$this->assertEquals('foo1', $Auth->data('foo'));
		$this->assertEquals('bar1', $Auth->data('bar'));
		$this->assertEquals(['foo' => 'foo1', 'bar' => 'bar1'], $Auth->data());
	}

	/**
	 * @depends testConstruct
	 * @param Auth $Auth
	 */
	public function testGID(Auth $Auth) {
		$this->assertEquals(11, $Auth->GID());
	}

	/**
	 * @depends testConstruct
	 * @param Auth $Auth
	 */
	public function testGROUP(Auth $Auth) {
		$this->assertEquals('admin', $Auth->GROUP());
	}

	/**
	 * @depends testConstruct
	 * @param Auth $Auth
	 */
	public function testNAME(Auth $Auth) {
		$this->assertEquals('John Black', $Auth->NAME());
	}

	/**
	 * @depends testConstruct
	 * @param Auth $Auth
	 */
	public function testUID(Auth $Auth) {
		$this->assertEquals(11, $Auth->UID());
	}
}
