<?php
namespace test;

use renovant\core\sys,
renovant\core\SysBoot,
renovant\core\SysException,
renovant\core\auth\Auth,
renovant\core\authz\authz,
renovant\core\console\CmdManager,
renovant\core\context\ContextException,
renovant\core\event\EventDispatcherException;

class sysTest extends \PHPUnit\Framework\TestCase {
	public const HTTP_ROUTES = [
		'MNGR'    => ['url' => '/',			'namespace' => 'mngr'],
		'API_FOO' => ['url' => '/api/foo/',	'namespace' => 'api.foo'],
		'API_BAR' => ['url' => '/api/bar/',	'namespace' => 'api.bars'],
		'UI'      => ['url' => '/',			'namespace' => 'ui']
	];

	public function testConstants() {
		$this->assertEquals('3.0.0', \renovant\core\VERSION);
		$this->assertEquals(\renovant\core\DIR, realpath(__DIR__ . '/../src/'));
	}

	/**
	 * @throws \renovant\core\util\yaml\YamlException|\ReflectionException
	 */
	public function testBoot() {
		SysBoot::boot();
		$ReflProp = new \ReflectionProperty('renovant\core\sys', 'Sys');
		$ReflProp->setAccessible(true);
		$Sys      = $ReflProp->getValue();
		$ReflProp = new \ReflectionProperty('renovant\core\sys', 'namespaces');
		$ReflProp->setAccessible(true);
		$namespaces = $ReflProp->getValue();

		// namespaces
		$this->assertArrayHasKey('renovant\core', $namespaces);
		$this->assertEquals(realpath(__DIR__ . '/../src'), $namespaces['renovant\core']);
		$this->assertArrayHasKey('test', $namespaces);
		$this->assertEquals(__DIR__, realpath($namespaces['test']));

		// constants
		$ReflProp = new \ReflectionProperty('renovant\core\sys', 'cnfConstants');
		$ReflProp->setAccessible(true);
		$constants = $ReflProp->getValue($Sys);
		$this->assertArrayHasKey('ASSETS_DIR', $constants);
		$this->assertEquals('/var/www/devel.com/data/assets/', $constants['ASSETS_DIR']);

		// settings
		$ReflProp = new \ReflectionProperty('renovant\core\sys', 'cnfSettings');
		$ReflProp->setAccessible(true);
		$settings = $ReflProp->getValue($Sys);
		$this->assertArrayHasKey('timeZone', $settings);
		$this->assertEquals('Europe/London', $settings['timeZone']);

		// Cache service
		$ReflProp = new \ReflectionProperty('renovant\core\sys', 'cnfCache');
		$ReflProp->setAccessible(true);
		$caches = $ReflProp->getValue($Sys);
		$this->assertArrayHasKey('main', $caches);
		$this->assertEquals('renovant\core\cache\SqliteCache', $caches['main']['class']);

		// DB service
		$ReflProp = new \ReflectionProperty('renovant\core\sys', 'cnfPdo');
		$ReflProp->setAccessible(true);
		$pdo = $ReflProp->getValue($Sys);
		$this->assertArrayHasKey('mysql', $pdo);
		$this->assertEquals('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname=phpunit', $pdo['mysql']['dns']);

		// LOG service
		$ReflProp = new \ReflectionProperty('renovant\core\sys', 'cnfLog');
		$ReflProp->setAccessible(true);
		$log = $ReflProp->getValue($Sys);
		$this->assertArrayHasKey('kernel', $log);
		$this->assertEquals('LOG_INFO', $log['kernel']['level']);
	}

	/**
	 * @depends testBoot
	 * @throws \renovant\core\container\ContainerException
	 * @throws \renovant\core\context\ContextException
	 * @throws \renovant\core\event\EventDispatcherException
	 * @throws \renovant\core\util\yaml\YamlException
	 * @throws \ReflectionException
	 */
	public function testInit() {
		sys::init('sys', 'system');
		$this->assertTrue(file_exists(sys::SYS_YAML_CACHE));
		restore_error_handler();
		restore_exception_handler();
	}

	/**
	 * @depends testInit
	 */
	public function testAuth() {
		$AUTH = sys::auth();
		$this->assertInstanceOf(Auth::class, $AUTH);
	}

	/**
	 * @depends testInit
	 */
	public function testAuthz() {
		$Authz = sys::authz();
		$this->assertInstanceOf(Authz::class, $Authz);
	}

	/**
	 * @depends testInit
	 */
	public function testAutoload() {
		sys::autoload('renovant\core\util\Date');
		$this->assertTrue(class_exists('renovant\core\util\Date', false));
	}

	/**
	 * @depends testInit
	 */
	public function testCache() {
		$this->assertInstanceOf('renovant\core\cache\CacheInterface', sys::cache('sys'));
		$this->assertInstanceOf('renovant\core\cache\CacheInterface', sys::cache());
	}

	/**
	 * @depends testInit
	 * @throws ContextException
	 * @throws EventDispatcherException
	 * @throws \ReflectionException
	 */
	public function testCmd() {
		sys::cache('sys')->delete('sys.CmdManager');
		$CmdManager = sys::cmd();
		$this->assertInstanceOf(CmdManager::class, $CmdManager);
	}

	/**
	 * @depends testInit
	 */
	public function testPdo() {
		$this->assertInstanceOf('renovant\core\db\PDO', sys::pdo('mysql'));
	}

	/**
	 * @depends testInit
	 */
	public function testPdoException() {
		try {
			sys::pdo('WRONG');
			$this->fail('Expected PDOException not thrown');
		} catch (\PDOException $Ex) {
			$this->assertEquals(0, $Ex->getCode());
			$this->assertMatchesRegularExpression('/valid data source name/', $Ex->getMessage());
		}
	}
}
