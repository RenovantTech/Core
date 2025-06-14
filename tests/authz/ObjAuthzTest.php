<?php
namespace test\authz;

use renovant\core\sys;
use renovant\core\authz\Authz,
renovant\core\authz\AuthzException,
renovant\core\authz\AuthzService,
renovant\core\util\reflection\ReflectionClass;

use const renovant\core\SYS_CACHE;

class ObjAuthzTest extends \PHPUnit\Framework\TestCase {
	public static $AuthService;
	public static $AuthzService;
	public static $TraitMockService;

	public static function setUpBeforeClass(): void {
		sys::cache('sys')->delete('sys.AUTHZ');
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS sys_authz_rules;
			DROP TABLE IF EXISTS sys_authz_maps;
			DROP TABLE IF EXISTS sys_authz;
			DROP TABLE IF EXISTS sys_users;
		');

		self::$AuthzService = new AuthzService('mysql', [
			'authz' => 'sys_authz',
			'users' => 'sys_users'
		]);
		sys::pdo('mysql')->exec(file_get_contents(__DIR__ . '/ObjAuthzTest.sql'));
	}

	public static function tearDownAfterClass(): void {
		sys::cache('sys')->delete('sys.AUTHZ');
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS sys_authz_rules;
			DROP TABLE IF EXISTS sys_authz_maps;
			DROP TABLE IF EXISTS sys_authz;
			DROP TABLE IF EXISTS sys_users;
		');
	}

	protected static function authenticate($userId) {
		$RefClass = new ReflectionClass(Authz::class);
		$RefProp  = $RefClass->getProperty('_Authz');
		$RefProp->setAccessible(true);
		$RefProp->setValue(null);
		sys::cache(SYS_CACHE)->delete(AuthzService::CACHE_PREFIX . $userId);

		try {
			$AuthService = sys::context()->get('sys.AUTH');
			$AuthService->authenticate($userId, null, '', '');
			$AuthzService = sys::context()->get('sys.AUTHZ');
			$AuthzService->init();
		} catch (\Exception) {
		}
	}

	/**
	 * @return ObjAuthzMock
	 * @throws \ReflectionException
	 * @throws \renovant\core\context\ContextException
	 * @throws \renovant\core\event\EventDispatcherException
	 */
	public function testConstruct() {
		/** @var ObjAuthzMock $ObjAuthzMock */
		$ObjAuthzMock = sys::context()->get('test.authz.ObjAuthzMock');
		$this->assertInstanceOf(\renovant\core\CoreProxy::class, $ObjAuthzMock);
		return $ObjAuthzMock;
	}

	/**
	 * @depends testConstruct
	 * @throws AuthzException
	 * @throws \ReflectionException
	 */
	public function testRole($ObjAuthzMock) {
		// GLOBAL Authz
		self::authenticate(6);
		$this->assertEquals('allow-roles', $ObjAuthzMock->allowRoles());
		$this->assertEquals('role', $ObjAuthzMock->role());

		self::authenticate(1);
		$this->assertEquals('allow-roles', $ObjAuthzMock->allowRoles());

		self::authenticate(1);
		$this->assertEquals(0, sys::authz()->verified());
		$this->assertEquals('role', $ObjAuthzMock->role());
		$this->assertEquals(1, sys::authz()->verified());

		self::authenticate(3);
		$this->assertEquals('roles-all', $ObjAuthzMock->rolesAll());

		self::authenticate(2);
		$this->assertEquals('roles-any', $ObjAuthzMock->rolesAny());
	}

	/**
	 * @depends testConstruct
	 * @throws \ReflectionException
	 */
	public function testRoleException($ObjAuthzMock) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(300);
		$this->expectExceptionMessage('[ROLE] "role.service"');

		self::authenticate(2);
		$this->assertEquals('allow-roles', $ObjAuthzMock->allowRoles());

		self::authenticate(4);
		$this->assertEquals('role', $ObjAuthzMock->role());
	}

	/**
	 * @depends testConstruct
	 * @throws \ReflectionException
	 */
	public function testRolesAllException($ObjAuthzMock) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(301);
		$this->expectExceptionMessage('[ROLE] "role.service.foo"');

		self::authenticate(2);
		$this->assertEquals('roles-all', $ObjAuthzMock->rolesAll());
	}

	/**
	 * @depends testConstruct
	 * @throws \ReflectionException
	 */
	public function testRolesAnyException($ObjAuthzMock) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(301);
		$this->expectExceptionMessage('[ROLE] "role.service.foo, role.service.bar"');

		self::authenticate(5);
		$this->assertEquals('roles-any', $ObjAuthzMock->rolesAny());
	}

	/**
	 * @depends testConstruct
	 * @throws AuthzException
	 * @throws \ReflectionException
	 */
	public function testPermission($ObjAuthzMock) {
		// GLOBAL Authz
		self::authenticate(6);
		$this->assertEquals('permission', $ObjAuthzMock->permission());
		$this->assertEquals('permissions-all', $ObjAuthzMock->permissionsAll());
		$this->assertEquals('permissions-any', $ObjAuthzMock->permissionsAny());

		self::authenticate(1);
		$this->assertEquals('permission', $ObjAuthzMock->permission());

		self::authenticate(3);
		$this->assertEquals('permissions-all', $ObjAuthzMock->permissionsAll());

		self::authenticate(2);
		$this->assertEquals('permissions-any', $ObjAuthzMock->permissionsAny());
	}

	/**
	 * @depends testConstruct
	 * @throws \ReflectionException
	 */
	public function testPermissionException($ObjAuthzMock) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(401);
		$this->expectExceptionMessage('[PERMISSION] "perm.service.foo"');

		self::authenticate(5);
		$this->assertEquals('permission', $ObjAuthzMock->permission());
	}

	/**
	 * @depends testConstruct
	 * @throws \ReflectionException
	 */
	public function testPermissionsAllException($ObjAuthzMock) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(401);
		$this->expectExceptionMessage('[PERMISSION] "perm.service.foo"');

		self::authenticate(2);
		$this->assertEquals('permissions-all', $ObjAuthzMock->permissionsAll());
	}

	/**
	 * @depends testConstruct
	 * @throws \ReflectionException
	 */
	public function testPermissionsAnyException($ObjAuthzMock) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(401);
		$this->expectExceptionMessage('[PERMISSION] "perm.service.foo, perm.service.bar"');

		self::authenticate(5);
		$this->assertEquals('permissions-any', $ObjAuthzMock->permissionsAny());
	}

	/**
	 * @depends testConstruct
	 * @throws AuthzException
	 * @throws \ReflectionException
	 */
	public function testAcl($ObjAuthzMock) {
		// GLOBAL Authz
		self::authenticate(6);
		$this->assertEquals('acl-12-34-123', $ObjAuthzMock->acl(12, 34, 123));
		$this->assertEquals('acl-all-1-1-123', $ObjAuthzMock->aclAll(1, 1, 123));
		$this->assertEquals('acl-any-1-9-123', $ObjAuthzMock->aclAny(1, 9, 123));

		self::authenticate(1);
		$this->assertEquals('acl-12-34-123', $ObjAuthzMock->acl(12, 34, 123));
		$this->assertEquals('acl-12-34-456', $ObjAuthzMock->acl(12, 34, 456));

		self::authenticate(2);
		$this->assertEquals('acl-all-1-1-123', $ObjAuthzMock->aclAll(1, 1, 123));
		$this->assertEquals('acl-all-2-2-123', $ObjAuthzMock->aclAll(2, 2, 123));

		self::authenticate(3);
		$this->assertEquals('acl-any-1-9-123', $ObjAuthzMock->aclAny(1, 9, 123));
		$this->assertEquals('acl-any-2-9-123', $ObjAuthzMock->aclAny(2, 9, 123));
	}

	/**
	 * @depends testConstruct
	 * @throws \ReflectionException
	 */
	public function testAclException($ObjAuthzMock) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(101);
		$this->expectExceptionMessage('[ACL] "acl.foo"');

		self::authenticate(1);
		$this->assertEquals('acl-12-34-789', $ObjAuthzMock->acl(12, 34, 789));
	}

	/**
	 * @depends testConstruct
	 * @throws \ReflectionException
	 */
	public function testAclAllException($ObjAuthzMock) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(101);
		$this->expectExceptionMessage('[ACL] "acl.district"');

		self::authenticate(3);
		$this->assertEquals('acl-all-1-9-123', $ObjAuthzMock->aclAll(1, 9, 123));
	}

	/**
	 * @depends testConstruct
	 * @throws \ReflectionException
	 */
	public function testAclAnyException($ObjAuthzMock) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(101);
		$this->expectExceptionMessage('[ACL] "acl.area, acl.district"');

		self::authenticate(1);
		$this->assertEquals('acl-any-1-1-123', $ObjAuthzMock->aclAny(1, 1, 123));
	}
}
