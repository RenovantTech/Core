<?php
namespace test\authz;
use const renovant\core\SYS_CACHE;
use renovant\core\sys,
	renovant\core\auth\AuthServiceJWT,
	renovant\core\authz\Authz,
	renovant\core\authz\AuthzException,
	renovant\core\authz\AuthzService,
	renovant\core\util\reflection\ReflectionClass;

class AuthzTraitTest extends \PHPUnit\Framework\TestCase {

	static $AuthService;
	static $AuthzService;
	static $TraitMockService;

	/**
	 * @throws \ReflectionException
	 * @throws \renovant\core\event\EventDispatcherException
	 * @throws \renovant\core\context\ContextException
	 */
	static function setUpBeforeClass():void {
		sys::cache('sys')->delete('sys.AUTHZ');
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS sys_authz_rules;
			DROP TABLE IF EXISTS sys_authz_maps;
			DROP TABLE IF EXISTS sys_authz;
			DROP TABLE IF EXISTS sys_users;
		');

		self::$AuthzService = new AuthzService( ['ORM', 'ROUTING', 'SERVICES'], 'mysql', [
			'authz'	=> 'sys_authz',
			'users'	=> 'sys_users'
		]);
		sys::pdo('mysql')->exec(file_get_contents(__DIR__ . '/AuthzTraitTest.sql'));
		self::$AuthService = new AuthServiceJWT();
		self::$TraitMockService = sys::context()->get('test.authz.AuthzTraitMockService');
	}

	static function tearDownAfterClass():void {
		sys::cache('sys')->delete('sys.AUTHZ');
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS sys_authz_rules;
			DROP TABLE IF EXISTS sys_authz_maps;
			DROP TABLE IF EXISTS sys_authz;
			DROP TABLE IF EXISTS sys_users;
		');
	}

	static protected function reset($userId) {
		$RefClass = new ReflectionClass(Authz::class);
		$RefProp = $RefClass->getProperty('_Authz');
		$RefProp->setAccessible(true);
		$RefProp->setValue(null);
		sys::cache(SYS_CACHE)->delete(AuthzService::CACHE_PREFIX.$userId);
	}

	/**
	 * @return AuthzTraitMockService
	 * @throws \ReflectionException
	 * @throws \renovant\core\context\ContextException
	 * @throws \renovant\core\event\EventDispatcherException
	 */
	function testConstruct() {
		/** @var AuthzTraitMockService $AuthzTraitMockService */
		$AuthzTraitMockService = sys::context()->get('test.authz.AuthzTraitMockService');
		$this->assertInstanceOf(\renovant\core\CoreProxy::class, $AuthzTraitMockService);
		return $AuthzTraitMockService;
	}

	/**
	 * @depends testConstruct
	 * @throws AuthzException
	 * @throws \ReflectionException
	 */
	function testAuthz($AuthzTraitMockService) {
		self::$AuthService->authenticate(1, null, '', '');
		self::reset(1);
		self::$AuthzService->init();
		$this->assertEquals('foo', $AuthzTraitMockService->foo());

		self::$AuthService->authenticate(2, null, '', '');
		self::reset(2);
		self::$AuthzService->init();
		$this->assertEquals('bar', $AuthzTraitMockService->bar());
	}

	/**
	 * @depends testConstruct
	 * @throws \ReflectionException
	 */
	function testAuthzActionException($AuthzTraitMockService) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(101);
		$this->expectExceptionMessage('[ACTION] "action.foo"');

		self::$AuthService->authenticate(2, null, '', '');
		self::reset(2);
		self::$AuthzService->init();
		$this->assertEquals('foo', $AuthzTraitMockService->foo());
	}

	/**
	 * @depends testConstruct
	 * @throws \ReflectionException
	 */
	function testAuthzFilterException($AuthzTraitMockService) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(201);
		$this->expectExceptionMessage('[FILTER] "filter.bar"');

		self::$AuthService->authenticate(1, null, '', '');
		self::reset(1);
		self::$AuthzService->init();
		$this->assertEquals('bar', $AuthzTraitMockService->bar());
	}

	/**
	 * @depends testConstruct
	 * @throws \ReflectionException
	 */
	function testAuthzRoleException($AuthzTraitMockService) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(300);
		$this->expectExceptionMessage('[ROLE] "role.service2"');

		self::$AuthService->authenticate(3, null, '', '');
		self::reset(3);
		self::$AuthzService->init();
		$this->assertEquals('foo', $AuthzTraitMockService->foo());
	}
}
