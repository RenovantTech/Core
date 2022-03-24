<?php
namespace test\acl;
use renovant\core\sys,
	renovant\core\acl\AclService,
	renovant\core\auth\AuthServiceJWT,
	renovant\core\http\Request;

class AclServiceTest extends \PHPUnit\Framework\TestCase {

	static function setUpBeforeClass():void {
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS sys_acl_rules;
			DROP TABLE IF EXISTS sys_acl_maps;
			DROP TABLE IF EXISTS sys_acl;
			DROP TABLE IF EXISTS sys_users;
		');
	}

	static function tearDownAfterClass():void {
		/*
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS sys_acl_rules;
			DROP TABLE IF EXISTS sys_acl_maps;
			DROP TABLE IF EXISTS sys_acl;
			DROP TABLE IF EXISTS sys_users;
		');
		*/
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

		$this->assertTrue(constant('SYS_ACL_ORM'));
		$this->assertTrue(constant('SYS_ACL_ROUTING'));
		$this->assertTrue(constant('SYS_ACL_SERVICES'));
		return $AclService;
	}

	/**
	 * @depends testConstruct
	 * @param AclService $AclService
	 * @throws \ReflectionException
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
	}

	/**
	 * @depends testConstruct
	 * @param ACL $ACL
	 * @throws \Exception
	 */
	function __testOnRoute(ACL $ACL) {
		$Req = new Request('/api/users/', 'GET', ['type'=>'all']);
		$this->assertTrue($ACL->onRoute($Req, 1));

		$Req = new Request('/api/users/', 'POST', ['type'=>'all']);
		$this->assertTrue($ACL->onRoute($Req, 1));
	}

	/**
	 * @depends testConstruct
	 * @param ACL $ACL
	 * @throws \Exception
	 */
	function __testOnRouteException(ACL $ACL) {
		$this->expectException('renovant\core\acl\Exception');
		$this->expectExceptionCode(100);
		$this->expectExceptionMessage('[ACTION] "api.users.insert" DENIED');
		$Req = new Request('/api/users/', 'POST', ['type'=>'all']);
		$this->assertTrue($ACL->onRoute($Req, 2));
	}

	/**
	 * @depends testConstruct
	 * @param ACL $ACL
	 * @throws \Exception
	 */
	function __testOnObject(ACL $ACL) {
		$this->assertTrue($ACL->onObject('service.Foo', 'index', 1));
	}

	/**
	 * @depends testConstruct
	 * @param ACL $ACL
	 * @throws \Exception
	 */
	function __testOnObjectException(ACL $ACL) {
		$this->expectException('renovant\core\acl\Exception');
		$this->expectExceptionCode(100);
		$this->expectExceptionMessage('[ACTION] "service.Foo" DENIED');
		$this->assertTrue($ACL->onObject('service.Foo', 'index', 2));
	}

	/**
	 * @depends testConstruct
	 * @param ACL $ACL
	 * @throws \Exception
	 */
	function __testOnOrm(ACL $ACL) {
		$this->assertTrue($ACL->onOrm('data.UserRepository', 'FETCH', 1));
		$this->assertTrue($ACL->onOrm('data.UserRepository', 'FETCH', 4));
	}

	/**
	 * @depends testConstruct
	 * @param ACL $ACL
	 * @throws \Exception
	 */
	function __testOnOrmException(ACL $ACL) {
		$this->expectException('renovant\core\acl\Exception');
		$this->expectExceptionCode(200);
		$this->expectExceptionMessage('[FILTER] "data.UserRepository" value MISSING');
		$this->assertTrue($ACL->onOrm('data.UserRepository', 'FETCH', 2));
	}
}
