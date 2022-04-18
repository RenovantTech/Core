<?php
namespace test\authz;
use const renovant\core\SYS_CACHE;
use renovant\core\sys,
	renovant\core\authz\Authz,
	renovant\core\authz\AuthzException,
	renovant\core\authz\AuthzService,
	renovant\core\util\reflection\ReflectionClass;

class ObjAuthzTest extends \PHPUnit\Framework\TestCase {

	static $AuthService;
	static $AuthzService;
	static $TraitMockService;

	static function setUpBeforeClass():void {
		sys::cache('sys')->delete('sys.AUTHZ');
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS sys_authz_rules;
			DROP TABLE IF EXISTS sys_authz_maps;
			DROP TABLE IF EXISTS sys_authz;
			DROP TABLE IF EXISTS sys_users;
		');

		self::$AuthzService = new AuthzService('mysql', [
			'authz'	=> 'sys_authz',
			'users'	=> 'sys_users'
		]);
		sys::pdo('mysql')->exec(file_get_contents(__DIR__ . '/ObjAuthzTest.sql'));
	}

	static function tearDownAfterClass():void {
		sys::cache('sys')->delete('sys.AUTHZ');
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
	 * @return ObjAuthzMock
	 * @throws \ReflectionException
	 * @throws \renovant\core\context\ContextException
	 * @throws \renovant\core\event\EventDispatcherException
	 */
	function testConstruct() {
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
	function testAuthzRole($ObjAuthzMock) {
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
	function testAuthzRoleException($ObjAuthzMock) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(300);
		$this->expectExceptionMessage('[ROLE] "role.service"');

		self::authenticate(4);
		$this->assertEquals('role', $ObjAuthzMock->role());
	}

	/**
	 * @depends testConstruct
	 * @throws \ReflectionException
	 */
	function testAuthzRolesAllException($ObjAuthzMock) {
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
	function testAuthzRolesAnyException($ObjAuthzMock) {
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
	function testAuthzPermission($ObjAuthzMock) {
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
	function testAuthzPermissionException($ObjAuthzMock) {
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
	function testAuthzPermissionsAllException($ObjAuthzMock) {
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
	function testAuthzPermissionsAnyException($ObjAuthzMock) {
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
	function testAuthzAcl($ObjAuthzMock) {
		self::authenticate(1);
		$this->assertEquals('acl-12-34-123', $ObjAuthzMock->acl(12, 34, 123));
		$this->assertEquals('acl-12-34-456', $ObjAuthzMock->acl(12, 34, 456));

		self::authenticate(2);
		$this->assertEquals('acl-all-A1-D1-123', $ObjAuthzMock->aclAll('A1', 'D1', 123));
		$this->assertEquals('acl-all-A2-D2-123', $ObjAuthzMock->aclAll('A2', 'D2', 123));

		self::authenticate(3);
		$this->assertEquals('acl-any-A1-D9-123', $ObjAuthzMock->aclAny('A1', 'D9', 123));
		$this->assertEquals('acl-any-A2-D9-123', $ObjAuthzMock->aclAny('A2', 'D9', 123));
	}

	/**
	 * @depends testConstruct
	 * @throws \ReflectionException
	 */
	function testAuthzAclException($ObjAuthzMock) {
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
	function testAuthzAclAllException($ObjAuthzMock) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(101);
		$this->expectExceptionMessage('[ACL] "acl.district"');

		self::authenticate(3);
		$this->assertEquals('acl-all-A1-D9-123', $ObjAuthzMock->aclAll('A1', 'D9', 123));
	}

	/**
	 * @depends testConstruct
	 * @throws \ReflectionException
	 */
	function testAuthzAclAnyException($ObjAuthzMock) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(101);
		$this->expectExceptionMessage('[ACL] "acl.area, acl.district"');

		self::authenticate(1);
		$this->assertEquals('acl-any-A1-D1-123', $ObjAuthzMock->aclAny('A1', 'D1', 123));
	}
}
