<?php
namespace test\acl;
use const renovant\core\SYS_CACHE;
use renovant\core\sys,
	renovant\core\acl\ACL,
	renovant\core\acl\AclException,
	renovant\core\acl\AclService,
	renovant\core\auth\AuthServiceJWT,
	renovant\core\util\reflection\ReflectionClass;

class AclTraitTest extends \PHPUnit\Framework\TestCase {

	static $AclService;
	static $AuthService;
	static $MockService;

	static function setUpBeforeClass():void {
		sys::cache('sys')->delete('sys.ACL');
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS sys_acl_rules;
			DROP TABLE IF EXISTS sys_acl_maps;
			DROP TABLE IF EXISTS sys_acl;
			DROP TABLE IF EXISTS sys_users;
		');

		self::$AclService = new AclService( ['ORM', 'ROUTING', 'SERVICES'], 'mysql', [
			'acl'	=> 'sys_acl',
			'users'	=> 'sys_users'
		]);
		sys::pdo('mysql')->exec(file_get_contents(__DIR__.'/init2.sql'));
		self::$AuthService = new AuthServiceJWT();
		self::$MockService = sys::context()->get('test.acl.MockService2');
	}

	static function tearDownAfterClass():void {
		sys::cache('sys')->delete('sys.ACL');
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS sys_acl_rules;
			DROP TABLE IF EXISTS sys_acl_maps;
			DROP TABLE IF EXISTS sys_acl;
			DROP TABLE IF EXISTS sys_users;
		');
	}

	static protected function reset($userId) {
		$RefClass = new ReflectionClass(ACL::class);
		$RefProp = $RefClass->getProperty('_ACL');
		$RefProp->setAccessible(true);
		$RefProp->setValue(null);
		sys::cache(SYS_CACHE)->delete(AclService::CACHE_PREFIX.$userId);
	}

	/**
	 * @throws \ReflectionException|AclException
	 */
	function testAcl() {
		self::$AuthService->authenticate(1, null, '', '');
		self::reset(1);
		self::$AclService->init();
		/** @var MockService2 $MockService */
		$this->assertEquals('foo', self::$MockService->foo());

		self::$AuthService->authenticate(2, null, '', '');
		self::reset(2);
		self::$AclService->init();
		/** @var MockService2 $MockService */
		$this->assertEquals('bar', self::$MockService->bar());
	}

	/**
	 * @throws \ReflectionException
	 */
	function testAclActionException() {
		$this->expectException(AclException::class);
		$this->expectExceptionCode(101);
		$this->expectExceptionMessage('[ACTION] "action.foo"');

		self::$AuthService->authenticate(2, null, '', '');
		self::reset(2);
		self::$AclService->init();
		/** @var MockService2 $MockService */
		$this->assertEquals('foo', self::$MockService->foo());
	}

	/**
	 * @throws \ReflectionException
	 */
	function testAclFilterException() {
		$this->expectException(AclException::class);
		$this->expectExceptionCode(201);
		$this->expectExceptionMessage('[FILTER] "filter.bar"');

		self::$AuthService->authenticate(1, null, '', '');
		self::reset(1);
		self::$AclService->init();
		/** @var MockService2 $MockService */
		$this->assertEquals('bar', self::$MockService->bar());
	}

	/**
	 * @throws \ReflectionException
	 */
	function testAclRoleException() {
		$this->expectException(AclException::class);
		$this->expectExceptionCode(300);
		$this->expectExceptionMessage('[ROLE] "role.service2"');

		self::$AuthService->authenticate(3, null, '', '');
		self::reset(3);
		self::$AclService->init();
		/** @var MockService2 $MockService */
		$this->assertEquals('foo', self::$MockService->foo());
	}
}
