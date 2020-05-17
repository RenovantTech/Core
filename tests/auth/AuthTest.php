<?php
namespace test\auth;
use renovant\core\auth\Auth,
	renovant\core\auth\AuthException;

class AuthTest extends \PHPUnit\Framework\TestCase {

	static function setUpBeforeClass(): void {
		Auth::erase();
	}

	static function tearDownAfterClass():void {
		Auth::erase();
	}

	/**
	 * @return Auth
	 * @throws \renovant\core\auth\AuthException
	 */
	function testInit(): Auth {
		$Auth = Auth::init([
			'GID' => 11,
			'GROUP' => 'admin',
			'NAME' => 'John Black',
			'UID' => 11,
			'foo' => 'foo1',
			'bar' => 'bar1',

		]);
		$this->assertInstanceOf(Auth::class, $Auth);
		return $Auth;
	}

	/**
	 * @depends testInit
	 * @throws AuthException
	 */
	function testInitException() {
		$this->expectExceptionCode(1);
		$this->expectException(AuthException::class);
		Auth::init([]);
	}

	/**
	 * @depends testInit
	 */
	function testInstance() {
		$this->assertInstanceOf(Auth::class, Auth::instance());
	}

	/**
	 * @depends testInit
	 * @param Auth $Auth
	 */
	function testData(Auth $Auth) {
		$this->assertEquals('foo1', $Auth->data('foo'));
		$this->assertEquals('bar1', $Auth->data('bar'));
		$this->assertEquals(['foo'=>'foo1', 'bar'=>'bar1'], $Auth->data());
	}

	/**
	 * @depends testInit
	 * @param Auth $Auth
	 */
	function testGID(Auth $Auth) {
		$this->assertEquals(11, $Auth->GID());
	}

	/**
	 * @depends testInit
	 * @param Auth $Auth
	 */
	function testGROUP(Auth $Auth) {
		$this->assertEquals('admin', $Auth->GROUP());
	}

	/**
	 * @depends testInit
	 * @param Auth $Auth
	 */
	function testNAME(Auth $Auth) {
		$this->assertEquals('John Black', $Auth->NAME());
	}

	/**
	 * @depends testInit
	 * @param Auth $Auth
	 */
	function testUID(Auth $Auth) {
		$this->assertEquals(11, $Auth->UID());
	}
}
