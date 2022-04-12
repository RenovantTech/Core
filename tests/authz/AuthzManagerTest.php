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
			'id' => 1,
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
			'id' => 1,
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
		$this->assertTrue($AuthzManager->deleteDef(1));
	}
}
