<?php
namespace test\acl;
use function metadigit\core\pdo;
use metadigit\core\acl\ACL,
	metadigit\core\http\Request;

class ACLTest extends \PHPUnit_Framework_TestCase {

	static function setUpBeforeClass() {
		pdo('mysql')->exec('
			DROP TABLE IF EXISTS sys_users_2_groups;
			DROP TABLE IF EXISTS sys_users;
			DROP TABLE IF EXISTS sys_groups;
		');
	}

	static function tearDownAfterClass() {
		pdo('mysql')->exec('
			DROP TABLE IF EXISTS sys_acl_filters_2_users;
			DROP TABLE IF EXISTS sys_acl_filters_2_groups;
			DROP TABLE IF EXISTS sys_acl_actions_2_users;
			DROP TABLE IF EXISTS sys_acl_actions_2_groups;
			DROP TABLE IF EXISTS sys_acl;
			DROP TABLE IF EXISTS sys_acl_actions;
			DROP TABLE IF EXISTS sys_acl_filters;
			DROP TABLE IF EXISTS sys_acl_filters_sql;
			DROP TABLE IF EXISTS sys_users_2_groups;
			DROP TABLE IF EXISTS sys_users;
			DROP TABLE IF EXISTS sys_groups;
		');
	}

	function testConstruct() {
		$ACL = new ACL( 'sys_acl', 'sys_users', 'sys_groups', 'sys_users_2_groups', 'mysql');
		$this->assertInstanceOf('metadigit\core\acl\ACL', $ACL);
		pdo('mysql')->exec(file_get_contents(__DIR__.'/init.sql'));
		return $ACL;
	}

	/**
	 * @depends testConstruct
	 * @param ACL $ACL
	 */
	function testOnRoute(ACL $ACL) {
		$Req = new Request('/api/users/', 'GET', ['type'=>'all']);
		$_SESSION['UID'] = 1;
		$this->assertTrue($ACL->onRoute($Req));

		$Req = new Request('/api/users/', 'POST', ['type'=>'all']);
		$_SESSION['UID'] = 1;
		$this->assertTrue($ACL->onRoute($Req));
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
		$_SESSION['UID'] = 2;
		$this->assertTrue($ACL->onRoute($Req));
	}

	/**
	 * @depends testConstruct
	 * @param ACL $ACL
	 */
	function testOnObject(ACL $ACL) {
		$_SESSION['UID'] = 1;
		$this->assertTrue($ACL->onObject('service.Foo', 'index'));
	}

	/**
	 * @depends testConstruct
	 * @param ACL $ACL
	 */
	function testOnObjectException(ACL $ACL) {
		$this->expectException('metadigit\core\acl\Exception');
		$this->expectExceptionCode(100);
		$this->expectExceptionMessage('[ACTION] "service.Foo" DENIED');
		$_SESSION['UID'] = 2;
		$this->assertTrue($ACL->onObject('service.Foo', 'index'));
	}

	/**
	 * @depends testConstruct
	 * @param ACL $ACL
	 */
	function testOnOrm(ACL $ACL) {
		$_SESSION['UID'] = 1;
		$this->assertTrue($ACL->onOrm('data.UserRepository', 'FETCH'));

		$_SESSION['UID'] = 4;
		$this->assertTrue($ACL->onOrm('data.UserRepository', 'FETCH'));
	}

	/**
	 * @depends testConstruct
	 * @param ACL $ACL
	 */
	function testOnOrmException(ACL $ACL) {
		$this->expectException('metadigit\core\acl\Exception');
		$this->expectExceptionCode(200);
		$this->expectExceptionMessage('[FILTER] "data.UserRepository" value MISSING');
		$_SESSION['UID'] = 2;
		$this->assertTrue($ACL->onOrm('data.UserRepository', 'FETCH'));
	}
}
