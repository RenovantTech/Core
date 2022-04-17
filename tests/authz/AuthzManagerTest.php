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
			$AuthService->authenticate($userId, null, '', '');
			$AuthzService = sys::context()->get('sys.AUTHZ');
			$AuthzService->init();
		} catch (\Exception) {

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
	function testDefineRole(AuthzManager $AuthzManager) {
		$this->assertTrue($AuthzManager->defineRole('admin', 'Admin'));
	}

	/**
	 * @depends testConstruct
	 * @throws \ReflectionException
	 */
	function testDefineRoleException(AuthzManager $AuthzManager) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(500);
		$this->expectExceptionMessage('VALIDATION error: code');

		$this->assertTrue($AuthzManager->defineRole('admin foo', 'Admin'));
	}

	/**
	 * @depends testConstruct
	 * @throws \ReflectionException
	 * @throws \renovant\core\authz\AuthzException
	 */
	function testDefinePermission(AuthzManager $AuthzManager) {
		$this->assertTrue($AuthzManager->definePermission('reboot', 'lorem ipsum'));
	}

	/**
	 * @depends testConstruct
	 * @throws \ReflectionException
	 */
	function testDefinePermissionException(AuthzManager $AuthzManager) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(500);
		$this->expectExceptionMessage('VALIDATION error: code');

		$this->assertTrue($AuthzManager->definePermission('can write', 'lorem ipsum'));
	}

	/**
	 * @depends testConstruct
	 * @throws \ReflectionException
	 * @throws \renovant\core\authz\AuthzException
	 */
	function testDefineAcl(AuthzManager $AuthzManager) {
		$this->assertTrue($AuthzManager->defineAcl(
			'blog:author',
			'lorem ipsum',
			'SELECT id, name FROM blogs',
			'name LIKE :q',
			'id IN (:ids)'
		));
	}

	/**
	 * @depends testConstruct
	 * @throws \ReflectionException
	 */
	function testDefineAclException(AuthzManager $AuthzManager) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(500);
		$this->expectExceptionMessage('VALIDATION error: code');

		$this->assertTrue($AuthzManager->defineAcl('blog author', 'lorem ipsum', '', '', ''));
	}

	/**
	 * @depends testConstruct
	 * @throws \ReflectionException
	 * @throws \renovant\core\authz\AuthzException
	 */
	function testRename(AuthzManager $AuthzManager) {
		$this->assertTrue($AuthzManager->rename('ROLE', 'ADMIN', 'ADMIN-2'));
		$this->assertTrue($AuthzManager->rename('ROLE', 'ADMIN-2', 'ADMIN'));
	}

	/**
	 * @depends testConstruct
	 * @throws \ReflectionException
	 */
	function testRenameException(AuthzManager $AuthzManager) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(502);
		$this->expectExceptionMessage('[RENAME] ROLE "XXXX" NOT DEFINED');

		$this->assertTrue($AuthzManager->rename('ROLE', 'XXXX', 'XXXXX-2'));
	}

	/**
	 * @depends testConstruct
	 * @throws AuthzException|\ReflectionException
	 */
	function testDelete(AuthzManager $AuthzManager) {
		$this->assertTrue($AuthzManager->defineRole('new-role', 'new role'));
		$this->assertTrue($AuthzManager->delete('ROLE', 'new-role'));
	}

	/**
	 * @depends testConstruct
	 * @throws AuthzException
	 */
	function testSetUserRole(AuthzManager $AuthzManager) {
		$this->assertTrue($AuthzManager->setUserRole('ADMIN', 1));
		$this->assertFalse($AuthzManager->setUserRole('ADMIN', 1));

		self::authenticate(1);
		$this->assertTrue(sys::authz()->role('ADMIN'));
	}

	/**
	 * @depends testConstruct
	 */
	function testSetUserRoleException(AuthzManager $AuthzManager) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(611);
		$this->expectExceptionMessage('[SET] role "XXXXXXX" NOT DEFINED');
		$this->assertTrue($AuthzManager->setUserRole('XXXXXXX', 1));
	}

	/**
	 * @depends testConstruct
	 * @throws AuthzException
	 */
	function testRevokeUserRole(AuthzManager $AuthzManager) {
		$this->assertTrue($AuthzManager->revokeUserRole('ADMIN', 1));
		$this->assertFalse($AuthzManager->revokeUserRole('ADMIN', 1));

		self::authenticate(1);
		$this->assertFalse(sys::authz()->role('ADMIN'));
	}

	/**
	 * @depends testConstruct
	 */
	function testRevokeUserRoleException(AuthzManager $AuthzManager) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(631);
		$this->expectExceptionMessage('[REVOKE] role "XXXXXXX" NOT DEFINED');
		$this->assertTrue($AuthzManager->revokeUserRole('XXXXXXX', 1));
	}

	/**
	 * @depends testConstruct
	 * @throws AuthzException
	 */
	function testSetUserPermission(AuthzManager $AuthzManager) {
		$this->assertTrue($AuthzManager->setUserPermission('blog.edit', 1));
		$this->assertFalse($AuthzManager->setUserPermission('blog.edit', 1));

		self::authenticate(1);
		$this->assertTrue(sys::authz()->permissions('blog.edit'));
	}

	/**
	 * @depends testConstruct
	 */
	function testSetUserPermissionException(AuthzManager $AuthzManager) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(612);
		$this->expectExceptionMessage('[SET] permission "XXXXXXX" NOT DEFINED');
		$this->assertTrue($AuthzManager->setUserPermission('XXXXXXX', 1));
	}

	/**
	 * @depends testConstruct
	 * @throws AuthzException
	 */
	function testRevokeUserPermission(AuthzManager $AuthzManager) {
		$this->assertTrue($AuthzManager->revokeUserPermission('blog.edit', 1));
		$this->assertFalse($AuthzManager->revokeUserPermission('blog.edit', 1));

		self::authenticate(1);
		$this->assertFalse(sys::authz()->permissions('blog.edit'));
	}

	/**
	 * @depends testConstruct
	 */
	function testRevokeUserPermissionException(AuthzManager $AuthzManager) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(632);
		$this->expectExceptionMessage('[REVOKE] permission "XXXXXXX" NOT DEFINED');
		$this->assertTrue($AuthzManager->revokeUserPermission('XXXXXXX', 1));
	}

	/**
	 * @depends testConstruct
	 * @throws AuthzException
	 */
	function testSetUserAcl(AuthzManager $AuthzManager) {
		$this->assertTrue($AuthzManager->setUserAcl('blog.author', 1, [123,456]));
		$this->assertFalse($AuthzManager->setUserAcl('blog.author', 1, [123,456]));
		self::authenticate(1);
		$this->assertTrue(sys::authz()->acl('blog.author', 123));
		$this->assertTrue(sys::authz()->acl('blog.author', 456));

		$this->assertTrue($AuthzManager->setUserAcl('blog.author', 1, [123,456,789]));
		self::authenticate(1);
		$this->assertTrue(sys::authz()->acl('blog.author', 123));
		$this->assertTrue(sys::authz()->acl('blog.author', 456));
		$this->assertTrue(sys::authz()->acl('blog.author', 789));
	}

	/**
	 * @depends testConstruct
	 */
	function testSetUserAclException(AuthzManager $AuthzManager) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(613);
		$this->expectExceptionMessage('[SET] acl "XXXXXXX" NOT DEFINED');
		$this->assertTrue($AuthzManager->setUserAcl('XXXXXXX', 1, [123,234]));
	}

	/**
	 * @depends testConstruct
	 * @throws AuthzException
	 */
	function testSetUserAclItem(AuthzManager $AuthzManager) {
		$this->assertTrue($AuthzManager->setUserAclItem('blog.author', 2, 123));
		$this->assertFalse($AuthzManager->setUserAclItem('blog.author', 2, 123));
		self::authenticate(2);
		$this->assertTrue(sys::authz()->acl('blog.author', 123));

		$this->assertTrue($AuthzManager->setUserAclItem('blog.author', 2, 456));
		self::authenticate(2);
		$this->assertTrue(sys::authz()->acl('blog.author', 123));
		$this->assertTrue(sys::authz()->acl('blog.author', 456));
	}

	/**
	 * @depends testConstruct
	 */
	function testSetUserAclItemException(AuthzManager $AuthzManager) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(613);
		$this->expectExceptionMessage('[SET] acl "XXXXXXX" NOT DEFINED');
		$this->assertTrue($AuthzManager->setUserAclItem('XXXXXXX', 1, 123));
	}

	/**
	 * @depends testConstruct
	 * @throws AuthzException
	 */
	function testFetchUserAcl(AuthzManager $AuthzManager) {
		$this->assertTrue($AuthzManager->setUserAcl('blog.author', 1, [123,456]));
		$this->assertEquals([123,456], $AuthzManager->fetchUserAcl('blog.author', 1));

		$this->assertTrue($AuthzManager->setUserAcl('blog.author', 1, [123,456,789]));
		$this->assertEquals([123,456,789], $AuthzManager->fetchUserAcl('blog.author', 1));
	}

	/**
	 * @depends testConstruct
	 * @throws AuthzException
	 */
	function testRevokeUserAcl(AuthzManager $AuthzManager) {
		$this->assertTrue($AuthzManager->revokeUserAcl('blog.author', 1));
		$this->assertFalse($AuthzManager->revokeUserAcl('blog.author', 1));
		self::authenticate(1);
		$this->assertFalse(sys::authz()->acl('blog.author', 123));
		$this->assertFalse(sys::authz()->acl('blog.author', 456));

		$this->assertTrue($AuthzManager->revokeUserAcl('blog.author', 2));
		self::authenticate(2);
		$this->assertFalse(sys::authz()->acl('blog.author', 123));
		$this->assertFalse(sys::authz()->acl('blog.author', 456));
	}

	/**
	 * @depends testConstruct
	 */
	function testRevokeUserAclException(AuthzManager $AuthzManager) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(633);
		$this->expectExceptionMessage('[REVOKE] acl "XXXXXXX" NOT DEFINED');
		$this->assertTrue($AuthzManager->revokeUserAcl('XXXXXXX', 1));
	}

	/**
	 * @depends testConstruct
	 * @throws AuthzException
	 */
	function testRevokeUserAclItem(AuthzManager $AuthzManager) {
		$AuthzManager->setUserAcl('blog.author', 1, [123,456,789]);
		$this->assertTrue($AuthzManager->revokeUserAclItem('blog.author', 1, 123));
		$this->assertFalse($AuthzManager->revokeUserAclItem('blog.author', 1, 123));
		self::authenticate(1);
		$this->assertFalse(sys::authz()->acl('blog.author', 123));
		$this->assertTrue(sys::authz()->acl('blog.author', 456));

		$this->assertTrue($AuthzManager->revokeUserAclItem('blog.author', 1, 456));
		self::authenticate(1);
		$this->assertFalse(sys::authz()->acl('blog.author', 123));
		$this->assertFalse(sys::authz()->acl('blog.author', 456));
	}

	/**
	 * @depends testConstruct
	 */
	function testRevokeUserAclItemException(AuthzManager $AuthzManager) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(633);
		$this->expectExceptionMessage('[REVOKE] acl "XXXXXXX" NOT DEFINED');
		$this->assertTrue($AuthzManager->revokeUserAclItem('XXXXXXX', 1, 123));
	}
}
