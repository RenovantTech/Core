<?php
namespace test\authz;
use const renovant\core\SYS_CACHE;
use renovant\core\sys,
	renovant\core\authz\Authz,
	renovant\core\authz\AuthzException,
	renovant\core\authz\AuthzManager,
	renovant\core\authz\AuthzService,
	renovant\core\util\reflection\ReflectionClass;

class AuthzManagerTest extends \PHPUnit\Framework\TestCase {

	static function setUpBeforeClass():void {
		sys::cache('sys')->delete('sys.AuthzManager');
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS sys_authz_rules;
			DROP TABLE IF EXISTS sys_authz_maps;
			DROP TABLE IF EXISTS sys_authz;
			DROP TABLE IF EXISTS sys_users;
		');
	}

	static function _tearDownAfterClass():void {
		sys::cache('sys')->delete('sys.AuthzManager');
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS sys_authz_rules;
			DROP TABLE IF EXISTS sys_authz_maps;
			DROP TABLE IF EXISTS sys_authz;
			DROP TABLE IF EXISTS sys_users;
		');
	}

	static protected function authenticate($userId) {
		$RefClass = new ReflectionClass(Authz::class);
		$RefProp = $RefClass->getProperty('_Authz');
		$RefProp->setAccessible(true);
		$RefProp->setValue(null);
		sys::cache(SYS_CACHE)->delete(AuthzService::CACHE_PREFIX.$userId);

		try {
			$AuthService = sys::context()->get('sys.AUTH');
			$AuthService->authenticate(1, null, '', '');
			$AuthzService = sys::context()->get('sys.AUTHZ');
			$AuthzService->init();
		} catch (\Exception $Ex) {

		}
	}

	/**
	 * @return AuthzManager
	 * @throws \ReflectionException
	 * @throws \renovant\core\event\EventDispatcherException
	 * @throws \renovant\core\context\ContextException
	 */
	function testConstruct() {
		$AuthzService = new AuthzManager('mysql', [
			'authz'	=> 'sys_authz',
			'users'	=> 'sys_users'
		]);
		$this->assertInstanceOf(AuthzManager::class, $AuthzService);
		/** @var AuthzManager $AuthzManager */
		$AuthzManager = sys::context()->get('sys.AuthzManager');
		$this->assertInstanceOf(AuthzManager::class, $AuthzManager);
		sys::pdo('mysql')->exec(file_get_contents(__DIR__.'/AuthzManagerTest.sql'));
		return $AuthzManager;
	}

	/**
	 * @depends testConstruct
	 * @throws \ReflectionException
	 * @throws \renovant\core\authz\AuthzException
	 */
	function testCreateDef(AuthzManager $AuthzManager) {
		$this->assertTrue($AuthzManager->createDef([
			'type' => 'ROLE',
			'code' => 'admin',
			'label' => 'Admin',
			'query' => null
		]));
	}

	/**
	 * @depends testConstruct
	 * @throws \ReflectionException
	 */
	function testCreateDefException(AuthzManager $AuthzManager) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(500);
		$this->expectExceptionMessage('VALIDATION error: code');

		$AuthzManager->createDef([
			'type' => 'ROLE',
			'code' => 'admin foo',
			'label' => 'Admin',
			'query' => null
		]);
	}

	/**
	 * @depends testConstruct
	 * @throws \ReflectionException
	 * @throws \renovant\core\authz\AuthzException
	 */
	function testUpdateDef(AuthzManager $AuthzManager) {
		$this->assertTrue($AuthzManager->updateDef([
			'id' => 8,
			'type' => 'ROLE',
			'code' => 'admin',
			'label' => 'Administrator',
			'query' => null
		]));
	}

	/**
	 * @depends testConstruct
	 * @throws \ReflectionException
	 */
	function testUpdateDefException(AuthzManager $AuthzManager) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(500);
		$this->expectExceptionMessage('VALIDATION error: code');

		$AuthzManager->updateDef([
			'id' => 8,
			'type' => 'ROLE',
			'code' => 'admin foo',
			'label' => 'Administrator',
			'query' => null
		]);
	}

	/**
	 * @depends testConstruct
	 */
	function testDeleteDef(AuthzManager $AuthzManager) {
		$this->assertTrue($AuthzManager->deleteDef(8));
	}

	/**
	 * @depends testConstruct
	 * @throws AuthzException
	 */
	function testSetRole(AuthzManager $AuthzManager) {
		$this->assertTrue($AuthzManager->setRole('ADMIN', 1));
		$this->assertFalse($AuthzManager->setRole('ADMIN', 1));

		self::authenticate(1);
		$this->assertTrue(sys::authz()->role('ADMIN'));
	}

	/**
	 * @depends testConstruct
	 */
	function testSetRoleException(AuthzManager $AuthzManager) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(611);
		$this->expectExceptionMessage('[SET] role "XXXXXXX" NOT DEFINED');
		$this->assertTrue($AuthzManager->setRole('XXXXXXX', 1));
	}

	/**
	 * @depends testConstruct
	 * @throws AuthzException
	 */
	function testRevokeRole(AuthzManager $AuthzManager) {
		$this->assertTrue($AuthzManager->revokeRole('ADMIN', 1));
		$this->assertFalse($AuthzManager->revokeRole('ADMIN', 1));

		self::authenticate(1);
		$this->assertFalse(sys::authz()->role('ADMIN'));
	}

	/**
	 * @depends testConstruct
	 */
	function testRevokeRoleException(AuthzManager $AuthzManager) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(621);
		$this->expectExceptionMessage('[REVOKE] role "XXXXXXX" NOT DEFINED');
		$this->assertTrue($AuthzManager->revokeRole('XXXXXXX', 1));
	}

	/**
	 * @depends testConstruct
	 * @throws AuthzException
	 */
	function testSetPermission(AuthzManager $AuthzManager) {
		$this->assertTrue($AuthzManager->setPermission('blog.edit', 1));
		$this->assertFalse($AuthzManager->setPermission('blog.edit', 1));

		self::authenticate(1);
		$this->assertTrue(sys::authz()->permissions('blog.edit'));
	}

	/**
	 * @depends testConstruct
	 */
	function testSetPermissionException(AuthzManager $AuthzManager) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(612);
		$this->expectExceptionMessage('[SET] permission "XXXXXXX" NOT DEFINED');
		$this->assertTrue($AuthzManager->setPermission('XXXXXXX', 1));
	}

	/**
	 * @depends testConstruct
	 * @throws AuthzException
	 */
	function testRevokePermission(AuthzManager $AuthzManager) {
		$this->assertTrue($AuthzManager->revokePermission('blog.edit', 1));
		$this->assertFalse($AuthzManager->revokePermission('blog.edit', 1));

		self::authenticate(1);
		$this->assertFalse(sys::authz()->permissions('blog.edit'));
	}

	/**
	 * @depends testConstruct
	 */
	function testRevokePermissionException(AuthzManager $AuthzManager) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(622);
		$this->expectExceptionMessage('[REVOKE] permission "XXXXXXX" NOT DEFINED');
		$this->assertTrue($AuthzManager->revokePermission('XXXXXXX', 1));
	}

	/**
	 * @depends testConstruct
	 * @throws AuthzException
	 */
	function testSetAcl(AuthzManager $AuthzManager) {
		$this->assertTrue($AuthzManager->setAcl('blog.author', 1, 123));
		$this->assertFalse($AuthzManager->setAcl('blog.author', 1, 123));
		self::authenticate(1);
		$this->assertTrue(sys::authz()->acl('blog.author', 123));

		$this->assertTrue($AuthzManager->setAcl('blog.author', 1, 456));
		self::authenticate(1);
		$this->assertTrue(sys::authz()->acl('blog.author', 123));
		$this->assertTrue(sys::authz()->acl('blog.author', 456));
	}

	/**
	 * @depends testConstruct
	 */
	function testSetAclException(AuthzManager $AuthzManager) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(613);
		$this->expectExceptionMessage('[SET] acl "XXXXXXX" NOT DEFINED');
		$this->assertTrue($AuthzManager->setAcl('XXXXXXX', 1, 123));
	}

	/**
	 * @depends testConstruct
	 * @throws AuthzException
	 */
	function testRevokeAcl(AuthzManager $AuthzManager) {
		$this->assertTrue($AuthzManager->revokeAcl('blog.author', 1, 123));
		$this->assertFalse($AuthzManager->revokeAcl('blog.author', 1, 123));
		self::authenticate(1);
		$this->assertFalse(sys::authz()->acl('blog.author', 123));
		$this->assertTrue(sys::authz()->acl('blog.author', 456));

		$this->assertTrue($AuthzManager->revokeAcl('blog.author', 1, 456));
		self::authenticate(1);
		$this->assertFalse(sys::authz()->acl('blog.author', 123));
		$this->assertFalse(sys::authz()->acl('blog.author', 456));
	}

	/**
	 * @depends testConstruct
	 */
	function testRevokeAclException(AuthzManager $AuthzManager) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(623);
		$this->expectExceptionMessage('[REVOKE] acl "XXXXXXX" NOT DEFINED');
		$this->assertTrue($AuthzManager->revokeAcl('XXXXXXX', 1, 123));
	}

	/**
	 * @depends testConstruct
	 * @throws AuthzException
	 */
	function testReplaceAcl(AuthzManager $AuthzManager) {
		$AuthzManager->setAcl('blog.author', 1, 123);
		$AuthzManager->setAcl('blog.author', 1, 456);
		$AuthzManager->setAcl('blog.author', 1, 789);
		self::authenticate(1);
		$this->assertTrue(sys::authz()->acl('blog.author', 123));
		$this->assertTrue(sys::authz()->acl('blog.author', 456));
		$this->assertTrue(sys::authz()->acl('blog.author', 789));

		$this->assertTrue($AuthzManager->replaceAcl('blog.author', 1, [321, 654, 987]));
		self::authenticate(1);
		$this->assertFalse(sys::authz()->acl('blog.author', 123));
		$this->assertFalse(sys::authz()->acl('blog.author', 456));
		$this->assertFalse(sys::authz()->acl('blog.author', 789));
		$this->assertTrue(sys::authz()->acl('blog.author', 321));
		$this->assertTrue(sys::authz()->acl('blog.author', 654));
		$this->assertTrue(sys::authz()->acl('blog.author', 987));
	}

	/**
	 * @depends testConstruct
	 */
	function testReplaceAclException(AuthzManager $AuthzManager) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(633);
		$this->expectExceptionMessage('[REPLACE] acl "XXXXXXX" NOT DEFINED');
		$this->assertTrue($AuthzManager->replaceAcl('XXXXXXX', 1, [123, 456]));
	}
}
