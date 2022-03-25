<?php
namespace test\acl;
use renovant\core\sys,
	renovant\core\acl\AclService,
	renovant\core\auth\AuthServiceJWT;

class AclServiceTest extends \PHPUnit\Framework\TestCase {

	static function setUpBeforeClass():void {
		sys::cache('sys')->delete('sys.ACL');
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS sys_acl_rules;
			DROP TABLE IF EXISTS sys_acl_maps;
			DROP TABLE IF EXISTS sys_acl;
			DROP TABLE IF EXISTS sys_users;
		');
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
	 * @return AclService
	 * @throws \ReflectionException
	 * @throws \renovant\core\event\EventDispatcherException
	 * @throws \renovant\core\context\ContextException
	 */
	function testConstruct() {
		$AclService = new AclService( ['ORM', 'ROUTING', 'SERVICES'], 'mysql', [
			'acl'	=> 'sys_acl',
			'users'	=> 'sys_users'
		]);
		$this->assertInstanceOf(AclService::class, $AclService);
		/** @var AclService $AclService */
		$AclService = sys::context()->get('sys.ACL');
		$this->assertInstanceOf(AclService::class, $AclService);
		sys::pdo('mysql')->exec(file_get_contents(__DIR__.'/init.sql'));
		return $AclService;
	}

	/**
	 * @depends testConstruct
	 * @param AclService $AclService
	 * @throws \ReflectionException
	 * @throws \renovant\core\acl\AclException
	 */
	function testInit(AclService $AclService) {
		$AuthService = new AuthServiceJWT();

		$AuthService->authenticate(1, null, '', '');
		$AclService->init();
		$ACL = sys::acl();
		$this->assertTrue($ACL->action('api.users'));
		$this->assertFalse($ACL->action('service.Bar'));
		$this->assertTrue($ACL->role('ADMIN'));
		$this->assertFalse($ACL->role('STAFF'));

		$this->assertTrue(constant('SYS_ACL_ORM'));
		$this->assertTrue(constant('SYS_ACL_ROUTING'));
		$this->assertTrue(constant('SYS_ACL_SERVICES'));
	}
}
