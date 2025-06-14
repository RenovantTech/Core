<?php
namespace test\authz;

use renovant\core\sys,
renovant\core\authz\Authz,
renovant\core\authz\AuthzException,
renovant\core\authz\AuthzService,
renovant\core\db\orm\Repository,
renovant\core\util\reflection\ReflectionClass,
test\authz\orm\Entity1,
test\authz\orm\Entity2,
test\authz\orm\Entity3;

use const renovant\core\SYS_CACHE;

class OrmAuthzTest extends \PHPUnit\Framework\TestCase {
	public static $AuthzService;

	public static function setUpBeforeClass(): void {
		sys::cache('sys')->delete('sys.AUTHZ');
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS sys_authz_rules;
			DROP TABLE IF EXISTS sys_authz_maps;
			DROP TABLE IF EXISTS sys_authz;
			DROP TABLE IF EXISTS sys_users;
			DROP TABLE IF EXISTS classes;
		');

		self::$AuthzService = new AuthzService('mysql', [
			'authz' => 'sys_authz',
			'users' => 'sys_users'
		]);
		sys::pdo('mysql')->exec(file_get_contents(__DIR__ . '/OrmAuthzTest.sql'));
	}

	public static function tearDownAfterClass(): void {
		sys::cache('sys')->delete('sys.AUTHZ');
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS sys_authz_rules;
			DROP TABLE IF EXISTS sys_authz_maps;
			DROP TABLE IF EXISTS sys_authz;
			DROP TABLE IF EXISTS sys_users;
			DROP TABLE IF EXISTS classes;
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
	 * @return Repository
	 * @throws
	 */
	public function testRepository1() {
		$Repository = sys::context()->container()->get('test.authz.orm.Entity1Repository');
		$this->assertInstanceOf('renovant\core\db\orm\Repository', $Repository);
		return $Repository;
	}

	/**
	 * @return Repository
	 * @throws
	 */
	public function testRepository2() {
		$Repository = sys::context()->container()->get('test.authz.orm.Entity2Repository');
		$this->assertInstanceOf('renovant\core\db\orm\Repository', $Repository);
		return $Repository;
	}

	/**
	 * @return Repository
	 * @throws
	 */
	public function testRepository3() {
		$Repository = sys::context()->container()->get('test.authz.orm.Entity3Repository');
		$this->assertInstanceOf('renovant\core\db\orm\Repository', $Repository);
		return $Repository;
	}

	/**
	 * @depends testRepository1
	 * @throws
	 */
	public function testRole(Repository $Repository) {
		// @authz-allow-roles(sys-admin)
		self::authenticate(1);
		$Entity1 = $Repository->fetch(1);
		$this->assertInstanceOf(Entity1::class, $Entity1);
		$this->assertSame(1, $Entity1->id);

		// @authz-insert-role(admin:insert)
		self::authenticate(2);
		$Entity1 = $Repository->insertOne(null, ['school_id' => 1, 'type_id' => 0, 'status' => 'ACTIVE', 'name' => 'Math']);
		$this->assertInstanceOf('test\authz\orm\Entity1', $Entity1);
		$this->assertSame(7, $Entity1->id);

		// @authz-select-roles-any(admin:select1, admin:select2)
		self::authenticate(2);
		$data = $Repository->fetchAll(null, null, null, 'type_id,EQ,0');
		$this->assertSame(7, count($data));

		// @authz-update-roles-all(admin:update1, admin:update2)
		self::authenticate(3);
		$Entity1 = $Repository->updateOne(1, ['type_id' => 2]);
		$this->assertInstanceOf(Entity1::class, $Entity1);
		$this->assertSame(1, $Entity1->id);
	}

	/**
	 * @depends testRepository1
	 * @throws
	 */
	public function testRoleException(Repository $Repository) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(301);
		$this->expectExceptionMessage('[ROLE] "admin:insert"');

		// @authz-insert-role(admin:insert)
		self::authenticate(3);
		$Repository->insertOne(null, ['school_id' => 1, 'type_id' => 0, 'status' => 'ACTIVE', 'name' => 'Math']);
	}

	/**
	 * @depends testRepository1
	 * @throws
	 */
	public function testRolesAllException(Repository $Repository) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(301);
		$this->expectExceptionMessage('[ROLE] "admin:update1"');

		// @authz-update-roles-all(admin:update1, admin:update2)
		self::authenticate(2);
		$Repository->updateOne(1, ['type_id' => 2]);
	}

	/**
	 * @depends testRepository1
	 * @throws
	 */
	public function testRolesAnyException(Repository $Repository) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(301);
		$this->expectExceptionMessage('[ROLE] "admin:select1, admin:select2"');

		// @authz-select-roles-any(admin:select1, admin:select2)
		self::authenticate(3);
		$Repository->fetchAll(null, null, null, 'type_id,EQ,0');
	}

	/**
	 * @depends testRepository2
	 * @throws
	 */
	public function testPermission(Repository $Repository) {
		// @authz-allow-permissions(perm:all)
		self::authenticate(1);
		$Entity2 = $Repository->fetch(1);
		$this->assertInstanceOf(Entity2::class, $Entity2);
		$this->assertSame(1, $Entity2->id);

		// @authz-insert-permission(perm:insert)
		self::authenticate(2);
		$Entity2 = $Repository->insertOne(null, ['school_id' => 1, 'type_id' => 0, 'status' => 'ACTIVE', 'name' => 'Math']);
		$this->assertInstanceOf(Entity2::class, $Entity2);
		$this->assertSame(8, $Entity2->id);

		// @authz-select-permissions-any(perm:select1, perm:select2)
		self::authenticate(2);
		$data = $Repository->fetchAll(null, null, null, 'type_id,EQ,0');
		$this->assertSame(8, count($data));

		// @authz-update-permissions-all(perm:update1, perm:update2)
		self::authenticate(3);
		$Entity2 = $Repository->updateOne(1, ['type_id' => 2]);
		$this->assertInstanceOf(Entity2::class, $Entity2);
		$this->assertSame(1, $Entity2->id);
	}

	/**
	 * @depends testRepository2
	 * @throws
	 */
	public function testPermissionException(Repository $Repository) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(401);
		$this->expectExceptionMessage('[PERMISSION] "perm:insert"');

		// @authz-insert-permission(perm:insert)
		self::authenticate(3);
		$Repository->insertOne(null, ['school_id' => 1, 'type_id' => 0, 'status' => 'ACTIVE', 'name' => 'Math']);
	}

	/**
	 * @depends testRepository2
	 * @throws
	 */
	public function testPermissionsAllException(Repository $Repository) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(401);
		$this->expectExceptionMessage('[PERMISSION] "perm:update1"');

		// @authz-update-permissions-all(perm:update1, perm:update2)
		self::authenticate(2);
		$Repository->updateOne(1, ['type_id' => 2]);
	}

	/**
	 * @depends testRepository2
	 * @throws
	 */
	public function testPermissionsAnyException(Repository $Repository) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(401);
		$this->expectExceptionMessage('[PERMISSION] "perm:select1, perm:select2"');

		// @authz-select-permissions-any(perm:select1, perm:select2)
		self::authenticate(3);
		$Repository->fetchAll(null, null, null, 'type_id,EQ,0');
	}

	/**
	 * @depends testRepository3
	 * @throws
	 */
	public function testAcl(Repository $Repository) {
		self::authenticate(1);
		$Entity3 = $Repository->fetch(1);
		$this->assertInstanceOf(Entity3::class, $Entity3);
		$this->assertSame(1, $Entity3->id);

		// @authz-insert-acl-any(acl:school, acl:type)
		self::authenticate(1);
		$Entity3 = $Repository->insertOne(null, ['school_id' => 1, 'type_id' => 0, 'status' => 'ACTIVE', 'name' => 'Math']);
		$this->assertInstanceOf(Entity3::class, $Entity3);
		$this->assertSame(9, $Entity3->id);

		// @authz-update-acl-all(acl:school, acl:type)
		self::authenticate(1);
		$Entity3 = $Repository->updateOne(1, ['type_id' => 2]);
		$this->assertInstanceOf(Entity3::class, $Entity3);
		$this->assertSame(1, $Entity3->id);
	}

	/**
	 * @depends testRepository3
	 * @throws
	 */
	public function testAclException(Repository $Repository) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(100);
		$this->expectExceptionMessage('[ACL] "acl:id"');

		// @authz-acl(acl:id="id")
		self::authenticate(4);
		$Repository->fetch(3);
	}

	/**
	 * @depends testRepository3
	 * @throws
	 */
	public function testAclAllException(Repository $Repository) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(101);
		$this->expectExceptionMessage('[ACL] "acl:school"');

		// @authz-update-acl-all(acl:school, acl:type)
		self::authenticate(2);
		$Repository->updateOne(1, ['type_id' => 2]);
	}

	/**
	 * @depends testRepository3
	 * @throws
	 */
	public function testAclAnyException(Repository $Repository) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(101);
		$this->expectExceptionMessage('[ACL] "acl:school"');

		// @authz-insert-acl-any(acl:school, acl:type)
		self::authenticate(2);
		$Repository->insertOne(null, ['school_id' => 1, 'type_id' => 0, 'status' => 'ACTIVE', 'name' => 'Math']);
	}
}
