<?php
namespace test\acl;
use metadigit\core\Kernel,
	metadigit\core\context\Context;

class ACLTest extends \PHPUnit_Framework_TestCase {

	static function setUpBeforeClass() {
		Kernel::pdo('mysql')->exec('
			DROP TABLE IF EXISTS `sys_users_2_groups`;
			DROP TABLE IF EXISTS `sys_users`;
			DROP TABLE IF EXISTS `sys_groups`;
		');
	}

	static function tearDownAfterClass() {
		Kernel::pdo('mysql')->exec('
			DROP TABLE IF EXISTS `sys_acl_filters_2_users`;
			DROP TABLE IF EXISTS `sys_acl_filters_2_groups`;
			DROP TABLE IF EXISTS `sys_acl_actions_2_users`;
			DROP TABLE IF EXISTS `sys_acl_actions_2_groups`;
			DROP TABLE IF EXISTS `sys_acl`;
			DROP TABLE IF EXISTS `sys_acl_actions`;
			DROP TABLE IF EXISTS `sys_acl_filters`;
			DROP TABLE IF EXISTS `sys_acl_filters_sql`;
			DROP TABLE IF EXISTS `sys_users_2_groups`;
			DROP TABLE IF EXISTS `sys_users`;
			DROP TABLE IF EXISTS `sys_groups`;
		');
	}

	protected function setUp() {

	}

	function testConstruct() {
		$ACL = Context::factory('system')->get('system.ACL');
		$this->assertInstanceOf('metadigit\core\acl\ACL', $ACL);
	}
}
