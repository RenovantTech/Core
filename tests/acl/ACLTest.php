<?php
namespace test\acl;
use metadigit\core\sys,
	metadigit\core\acl\ACL,
	metadigit\core\http\Request;

class ACLTest extends \PHPUnit\Framework\TestCase {

	static function setUpBeforeClass() {
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS sys_acl_filters_2_users;
			DROP TABLE IF EXISTS sys_acl_filters_2_roles;
			DROP TABLE IF EXISTS sys_acl_actions_2_users;
			DROP TABLE IF EXISTS sys_acl_actions_2_roles;
			DROP TABLE IF EXISTS sys_acl;
			DROP TABLE IF EXISTS sys_acl_actions;
			DROP TABLE IF EXISTS sys_acl_filters;
			DROP TABLE IF EXISTS sys_acl_filters_sql;
			DROP TABLE IF EXISTS sys_users_2_roles;
			DROP TABLE IF EXISTS sys_users;
			DROP TABLE IF EXISTS sys_roles;
		');
	}

	static function tearDownAfterClass() {
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS sys_acl_filters_2_users;
			DROP TABLE IF EXISTS sys_acl_filters_2_roles;
			DROP TABLE IF EXISTS sys_acl_actions_2_users;
			DROP TABLE IF EXISTS sys_acl_actions_2_roles;
			DROP TABLE IF EXISTS sys_acl;
			DROP TABLE IF EXISTS sys_acl_actions;
			DROP TABLE IF EXISTS sys_acl_filters;
			DROP TABLE IF EXISTS sys_acl_filters_sql;
			DROP TABLE IF EXISTS sys_users_2_roles;
			DROP TABLE IF EXISTS sys_users;
			DROP TABLE IF EXISTS sys_roles;
		');
	}

	function testConstruct() {
		$ACL = new ACL( ['ORM', 'ROUTING', 'SERVICES'], 'mysql', [
			'acl'	=> 'sys_acl',
			'users'	=> 'sys_users',
			'roles'	=> 'sys_roles',
			'u2r'	=> 'sys_users_2_roles'
		]);
		$this->assertInstanceOf(ACL::class, $ACL);
		sys::pdo('mysql')->exec(file_get_contents(__DIR__.'/init.sql'));
		return $ACL;
	}

	/**
	 * @depends testConstruct
	 * @param ACL $ACL
	 */
	function testInit(ACL $ACL) {
		$ACL->init();
		$this->assertTrue(constant('SYS_ACL_ORM'));
		$this->assertTrue(constant('SYS_ACL_ROUTING'));
		$this->assertTrue(constant('SYS_ACL_SERVICES'));
	}

	/**
	 * @depends testConstruct
	 * @param ACL $ACL
	 */
	function testOnRoute(ACL $ACL) {
		$Req = new Request('/api/users/', 'GET', ['type'=>'all']);
		$this->assertTrue($ACL->onRoute($Req, 1));

		$Req = new Request('/api/users/', 'POST', ['type'=>'all']);
		$this->assertTrue($ACL->onRoute($Req, 1));
	}

	/**
	 * @depends testConstruct
	 * @param ACL $ACL
	 */
	function testOnRouteException(ACL $ACL) {
		$this->expectException('metadigit\core\acl\Exception');
		$this->expectExceptionCode(100);
		$this->expectExceptionMessage('[ACTION] "api.users.insert" DENIED');
		$Req = new Request('/api/users/', 'POST', ['type'=>'all']);
		$this->assertTrue($ACL->onRoute($Req, 2));
	}

	/**
	 * @depends testConstruct
	 * @param ACL $ACL
	 */
	function testOnObject(ACL $ACL) {
		$this->assertTrue($ACL->onObject('service.Foo', 'index', 1));
	}

	/**
	 * @depends testConstruct
	 * @param ACL $ACL
	 */
	function testOnObjectException(ACL $ACL) {
		$this->expectException('metadigit\core\acl\Exception');
		$this->expectExceptionCode(100);
		$this->expectExceptionMessage('[ACTION] "service.Foo" DENIED');
		$this->assertTrue($ACL->onObject('service.Foo', 'index', 2));
	}

	/**
	 * @depends testConstruct
	 * @param ACL $ACL
	 */
	function testOnOrm(ACL $ACL) {
		$this->assertTrue($ACL->onOrm('data.UserRepository', 'FETCH', 1));
		$this->assertTrue($ACL->onOrm('data.UserRepository', 'FETCH', 4));
	}

	/**
	 * @depends testConstruct
	 * @param ACL $ACL
	 */
	function testOnOrmException(ACL $ACL) {
		$this->expectException('metadigit\core\acl\Exception');
		$this->expectExceptionCode(200);
		$this->expectExceptionMessage('[FILTER] "data.UserRepository" value MISSING');
		$this->assertTrue($ACL->onOrm('data.UserRepository', 'FETCH', 2));
	}
}
