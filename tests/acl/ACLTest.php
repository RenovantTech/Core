<?php
namespace test\acl;
use metadigit\core\Kernel,
	metadigit\core\acl\ACL,
	metadigit\core\context\Context,
	metadigit\core\http\Request;

class ACLTest extends \PHPUnit_Framework_TestCase {

	static function setUpBeforeClass() {
		Kernel::pdo('mysql')->exec('
			DROP TABLE IF EXISTS sys_users_2_groups;
			DROP TABLE IF EXISTS sys_users;
			DROP TABLE IF EXISTS sys_groups;
		');
	}

	static function tearDownAfterClass() {
		Kernel::pdo('mysql')->exec('
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
		$ACL = Context::factory('system')->get('system.ACL');
		$this->assertInstanceOf('metadigit\core\acl\ACL', $ACL);
		Kernel::pdo('mysql')->exec(file_get_contents(__DIR__.'/init.sql'));
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
	 * @expectedException \metadigit\core\acl\Exception
	 * @expectedExceptionCode 100
	 * @param ACL $ACL
	 */
	function testOnRouteException(ACL $ACL) {
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
	 * @expectedException \metadigit\core\acl\Exception
	 * @expectedExceptionCode 100
	 * @param ACL $ACL
	 */
	function testOnObjectException(ACL $ACL) {
		$_SESSION['UID'] = 2;
		$this->assertTrue($ACL->onObject('service.Foo', 'index'));
	}

	/**
	 * @depends testConstruct
	 * @param ACL $ACL
	 */
	function testOnOrm(ACL $ACL) {
		$_SESSION['UID'] = 1;
		$this->assertTrue($ACL->onOrm('data.UserRepository', 'INSERT'));
	}
	/**
	 * @depends testConstruct
	 * @expectedException \metadigit\core\acl\Exception
	 * @expectedExceptionCode 100
	 * @param ACL $ACL
	 */
	function testOnOrmException(ACL $ACL) {
		$_SESSION['UID'] = 2;
		$this->assertTrue($ACL->onOrm('data.UserRepository', 'INSERT'));
	}

}
