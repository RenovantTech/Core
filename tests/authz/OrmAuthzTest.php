<?php
namespace test\authz;
use const renovant\core\SYS_CACHE;
use renovant\core\sys,
	renovant\core\authz\Authz,
	renovant\core\authz\AuthzException,
	renovant\core\authz\AuthzService,
	renovant\core\db\orm\Repository,
	renovant\core\util\reflection\ReflectionClass;

class OrmAuthzTest extends \PHPUnit\Framework\TestCase {

	static $AuthzService;

	static function setUpBeforeClass():void {
		sys::cache('sys')->delete('sys.AUTHZ');
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS sys_authz_rules;
			DROP TABLE IF EXISTS sys_authz_maps;
			DROP TABLE IF EXISTS sys_authz;
			DROP TABLE IF EXISTS sys_users;
			DROP TABLE IF EXISTS classes;
		');

		self::$AuthzService = new AuthzService('mysql', [
			'authz'	=> 'sys_authz',
			'users'	=> 'sys_users'
		]);
		sys::pdo('mysql')->exec(file_get_contents(__DIR__ . '/OrmAuthzTest.sql'));
	}

	static function tearDownAfterClass():void {
		sys::cache('sys')->delete('sys.AUTHZ');
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS sys_authz_rules;
			DROP TABLE IF EXISTS sys_authz_maps;
			DROP TABLE IF EXISTS sys_authz;
			DROP TABLE IF EXISTS sys_users;
			DROP TABLE IF EXISTS classes;
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
	 * @return Repository
	 * @throws \ReflectionException
	 * @throws \renovant\core\container\ContainerException
	 */
	function testConstruct() {
		$OrmRepository = sys::context()->container()->get('test.authz.SchoolsRepository');
		$this->assertInstanceOf('renovant\core\db\orm\Repository', $OrmRepository);
		return $OrmRepository;
	}

	/**
	 * @depends testConstruct
	 */
	function testAuthzRole(Repository $OrmRepository) {
		self::authenticate(1);

		var_dump(sys::authz());

		$OrmAuthzMock = $OrmRepository->fetch(1);
		$this->assertInstanceOf('test\authz\OrmAuthzMock', $OrmAuthzMock);
		$this->assertSame(1, $OrmAuthzMock->id);
	}

	/**
	 * @depends testConstruct
	 * @param Repository $OrmRepository
	 * @throws \ReflectionException
	 * @throws \renovant\core\context\ContextException
	 * @throws \renovant\core\db\orm\Exception
	 * @throws \renovant\core\event\EventDispatcherException
	 */
	function testAuthzRoleException(Repository $OrmRepository) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(300);
		$this->expectExceptionMessage('[ROLE] "admin"');

		self::authenticate(2);
		$OrmRepository->fetch(1);
	}

	/**
	 * @depends testConstruct
	 */
	function __testAuthzRolesAllException(Repository $OrmRepository) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(301);
		$this->expectExceptionMessage('[ROLE] "role.service.foo"');

		self::authenticate(2);
	}

	/**
	 * @depends testConstruct
	 */
	function __testAuthzRolesAnyException(Repository $OrmRepository) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(301);
		$this->expectExceptionMessage('[ROLE] "role.service.foo, role.service.bar"');

		self::authenticate(5);
	}

	/**
	 * @depends testConstruct
	 * @param Repository $OrmRepository
	 * @throws \ReflectionException
	 * @throws \renovant\core\context\ContextException
	 * @throws \renovant\core\db\orm\Exception
	 * @throws \renovant\core\event\EventDispatcherException
	 */
	function testAuthzPermission(Repository $OrmRepository) {
		self::authenticate(1);

		$OrmAuthzMock = $OrmRepository->fetch(1);
		$this->assertSame('ACTIVE', $OrmAuthzMock->status);

		$OrmAuthzMock->status = 'OLD';
		$OrmRepository->update($OrmAuthzMock);
		$OrmAuthzMock = $OrmRepository->fetch(1);
		$this->assertSame('OLD', $OrmAuthzMock->status);
	}

	/**
	 * @depends testConstruct
	 * @throws \renovant\core\db\orm\Exception
	 */
	function testAuthzPermissionException(Repository $OrmRepository) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(401);
		$this->expectExceptionMessage('[PERMISSION] "users:update"');

		self::authenticate(3);
		$OrmRepository->updateOne(1, ['status'=>'NEW']);
	}

	/**
	 * @depends testConstruct
	 */
	function __testAuthzPermissionsAllException(Repository $OrmRepository) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(401);
		$this->expectExceptionMessage('[PERMISSION] "perm.service.foo"');

		self::authenticate(2);
	}

	/**
	 * @depends testConstruct
	 */
	function __testAuthzPermissionsAnyException(Repository $OrmRepository) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(401);
		$this->expectExceptionMessage('[PERMISSION] "perm.service.foo, perm.service.bar"');

		self::authenticate(5);

	}

	/**
	 * @depends testConstruct
	 */
	function testAuthzAcl(Repository $OrmRepository) {
		self::authenticate(1);
		$OrmAuthzMock = $OrmRepository->fetch(1);
		$this->assertSame(1, $OrmAuthzMock->id);
		$OrmAuthzMock = $OrmRepository->fetch(5);
		$this->assertSame(5, $OrmAuthzMock->id);
	}

	/**
	 * @depends testConstruct
	 */
	function testAuthzAclException(Repository $OrmRepository) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(101);
		$this->expectExceptionMessage('[ACL] "acl.foo"');

		self::authenticate(1);
		$OrmAuthzMock = $OrmRepository->fetch(3);
	}

	/**
	 * @depends testConstruct
	 */
	function testAuthzAclAllException(Repository $OrmRepository) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(101);
		$this->expectExceptionMessage('[ACL] "acl.district"');

		self::authenticate(3);
	}

	/**
	 * @depends testConstruct
	 */
	function testAuthzAclAnyException(Repository $OrmRepository) {
		$this->expectException(AuthzException::class);
		$this->expectExceptionCode(101);
		$this->expectExceptionMessage('[ACL] "acl.area, acl.district"');

		self::authenticate(1);

	}
}
