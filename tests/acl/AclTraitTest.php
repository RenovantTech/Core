<?php
namespace test\acl;
use renovant\core\sys,
	renovant\core\acl\AclService,
	renovant\core\auth\AuthServiceJWT;

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

	/**
	 * @throws \ReflectionException
	 */
	function testAcl() {
		self::$AuthService->authenticate(1, null, '', '');
		self::$AclService->init();
		/** @var MockService2 $MockService */
		$this->assertEquals('foo', self::$MockService->foo());

		self::$AuthService->authenticate(2, null, '', '');
		self::$AclService->init();
		/** @var MockService2 $MockService */
		$this->assertEquals('bar', self::$MockService->bar());
	}

	/**
	 * @throws \Exception
	 */
	function testAclActionException() {
		$this->expectException(\renovant\core\acl\Exception::class);
		$this->expectExceptionCode(101);
		$this->expectExceptionMessage('[ACTION] "action.foo"');

		self::$AuthService->authenticate(2, null, '', '');
		self::$AclService->init();
		/** @var MockService2 $MockService */
		$this->assertEquals('foo', self::$MockService->foo());
	}

	/**
	 * @throws \Exception
	 */
	function testAclFilterException() {
		$this->expectException(\renovant\core\acl\Exception::class);
		$this->expectExceptionCode(201);
		$this->expectExceptionMessage('[FILTER] "filter.bar"');

		self::$AuthService->authenticate(1, null, '', '');
		self::$AclService->init();
		/** @var MockService2 $MockService */
		$this->assertEquals('bar', self::$MockService->bar());
	}

	/**
	 * @throws \Exception
	 */
	function testAclRoleException() {
		$this->expectException(\renovant\core\acl\Exception::class);
		$this->expectExceptionCode(300);
		$this->expectExceptionMessage('[ROLE] "role.service2"');

		self::$AuthService->authenticate(3, null, '', '');
		self::$AclService->init();
		/** @var MockService2 $MockService */
		$this->assertEquals('foo', self::$MockService->foo());
	}
}
