<?php
namespace test\authz;
use const renovant\core\SYS_CACHE;
use renovant\core\sys,
	renovant\core\auth\AuthServiceJWT,
	renovant\core\authz\Authz,
	renovant\core\authz\AuthzService,
	renovant\core\util\reflection\ReflectionClass;

class AuthzServiceTest extends \PHPUnit\Framework\TestCase {

	static function setUpBeforeClass():void {
		sys::cache('sys')->delete('sys.AUTHZ');
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS sys_authz_rules;
			DROP TABLE IF EXISTS sys_authz_maps;
			DROP TABLE IF EXISTS sys_authz;
			DROP TABLE IF EXISTS sys_users;
		');
	}

	static function _tearDownAfterClass():void {
		sys::cache('sys')->delete('sys.AUTHZ');
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS sys_authz_rules;
			DROP TABLE IF EXISTS sys_authz_maps;
			DROP TABLE IF EXISTS sys_authz;
			DROP TABLE IF EXISTS sys_users;
		');
	}

	static protected function reset($userId) {
		$RefClass = new ReflectionClass(Authz::class);
		$RefProp = $RefClass->getProperty('_Authz');
		$RefProp->setAccessible(true);
		$RefProp->setValue(null);
		sys::cache(SYS_CACHE)->delete(AuthzService::CACHE_PREFIX.$userId);
	}

	/**
	 * @return AuthzService
	 * @throws \ReflectionException
	 * @throws \renovant\core\event\EventDispatcherException
	 * @throws \renovant\core\context\ContextException
	 */
	function testConstruct() {
		$AuthzService = new AuthzService( ['ORM', 'ROUTING', 'SERVICES'], 'mysql', [
			'authz'	=> 'sys_authz',
			'users'	=> 'sys_users'
		]);
		$this->assertInstanceOf(AuthzService::class, $AuthzService);
		/** @var AuthzService $AuthzService */
		$AuthzService = sys::context()->get('sys.AUTHZ');
		$this->assertInstanceOf(AuthzService::class, $AuthzService);
		sys::pdo('mysql')->exec(file_get_contents(__DIR__.'/AuthzServiceTest.sql'));
		return $AuthzService;
	}

	/**
	 * @depends testConstruct
	 * @param AuthzService $AuthzService
	 * @throws \ReflectionException
	 * @throws \renovant\core\authz\AuthzException
	 */
	function testInit(AuthzService $AuthzService) {
		$AuthService = new AuthServiceJWT();

		$AuthService->authenticate(1, null, '', '');
		self::reset(1);
		$AuthzService->init();
		$Authz = sys::authz();

		$this->assertTrue($Authz->role('ADMIN'));
		$this->assertFalse($Authz->role('STAFF'));

		$this->assertTrue($Authz->permissions('blog.edit'));
		$this->assertTrue($Authz->permissions('blog.delete'));
		$this->assertFalse($Authz->permissions('blog.master'));

		$this->assertTrue($Authz->acl('blog.author', 123));
		$this->assertTrue($Authz->acl('blog.author', 456));
		$this->assertFalse($Authz->acl('blog.author', 789));

		$this->assertTrue(constant('SYS_AUTHZ_ORM'));
		$this->assertTrue(constant('SYS_AUTHZ_ROUTING'));
		$this->assertTrue(constant('SYS_AUTHZ_SERVICES'));
	}
}
