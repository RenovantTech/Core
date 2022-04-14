<?php
namespace test;
use renovant\core\sys,
	renovant\core\SysBoot,
	renovant\core\SysException,
	renovant\core\acl\ACL,
	renovant\core\auth\Auth,
	renovant\core\console\CmdManager,
	renovant\core\context\ContextException,
	renovant\core\event\EventDispatcherException;

class sysTest extends \PHPUnit\Framework\TestCase {

	const HTTP_ROUTES = [
		'MNGR'			=> [ 'url' => '/',			'namespace' => 'mngr' ],
		'API_FOO'		=> [ 'url' => '/api/foo/',	'namespace' => 'api.foo' ],
		'API_BAR'		=> [ 'url' => '/api/bar/',	'namespace' => 'api.bars' ],
		'UI'			=> [ 'url' => '/',			'namespace' => 'ui' ]
	];

	function testConstants() {
		$this->assertEquals('3.0.0', \renovant\core\VERSION);
		$this->assertEquals(\renovant\core\DIR, realpath(__DIR__.'/../src/'));
	}

	/**
	 * @throws \renovant\core\util\yaml\YamlException|\ReflectionException
	 */
	function testBoot() {
		SysBoot::boot();
		$ReflProp = new \ReflectionProperty('renovant\core\sys', 'Sys');
		$ReflProp->setAccessible(true);
		$Sys = $ReflProp->getValue();
		$ReflProp = new \ReflectionProperty('renovant\core\sys', 'namespaces');
		$ReflProp->setAccessible(true);
		$namespaces = $ReflProp->getValue();

		// namespaces
		$this->assertArrayHasKey('renovant\core', $namespaces);
		$this->assertEquals(realpath(__DIR__.'/../src'), $namespaces['renovant\core']);
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
	function testInit() {
		sys::init('sys', 'system');
		$this->assertTrue(file_exists(sys::SYS_YAML_CACHE));
	}

	function testInfo() {
		// test PHP namespaces
		$this->assertEquals('renovant\core\http', sys::info('renovant\core\http\Dispatcher', sys::INFO_NAMESPACE));
		$this->assertEquals('Dispatcher', sys::info('renovant\core\http\Dispatcher', sys::INFO_CLASS));
		$this->assertEquals(realpath(__DIR__.'/../src/http').'/Dispatcher', sys::info('renovant\core\http\Dispatcher', sys::INFO_PATH));
		$this->assertEquals(realpath(__DIR__.'/../src/http'), sys::info('renovant\core\http\Dispatcher', sys::INFO_PATH_DIR));
		$this->assertEquals('Dispatcher', sys::info('renovant\core\http\Dispatcher', sys::INFO_PATH_FILE));
		list($namespace, $className, $dir, $file) = sys::info('renovant\core\http\Dispatcher');
		$this->assertEquals('renovant\core\http', $namespace);
		$this->assertEquals('Dispatcher', $className);
		$this->assertEquals(realpath(__DIR__.'/../src/http'), $dir);
		$this->assertEquals('Dispatcher', $file);
	}

	/**
	 * @depends testInit
	 */
	function testInfo2() {
		// test ID namespaces
		$this->assertEquals('test\app', sys::info('test.app.Dispatcher', sys::INFO_NAMESPACE));
		$this->assertEquals('Dispatcher', sys::info('test.app.Dispatcher', sys::INFO_CLASS));
		$this->assertEquals(TEST_DIR.'/app/Dispatcher', sys::info('test.app.Dispatcher', sys::INFO_PATH));
		$this->assertEquals(TEST_DIR.'/app', sys::info('test.app.Dispatcher', sys::INFO_PATH_DIR));
		$this->assertEquals('Dispatcher', sys::info('test.app.Dispatcher', sys::INFO_PATH_FILE));
		list($namespace, $className, $dir, $file) = sys::info('test.app.Dispatcher');
		$this->assertEquals('test\app', $namespace);
		$this->assertEquals('Dispatcher', $className);
		$this->assertEquals(TEST_DIR.'/app', $dir);
		$this->assertEquals('Dispatcher', $file);
	}

	/**
	 * @depends testInit
	 * @throws EventDispatcherException
	 * @throws ContextException|\ReflectionException
	 */
	function testAcl() {
		$ACL = sys::acl();
		$this->assertInstanceOf(ACL::class, $ACL);
		$RefProp = new \ReflectionProperty('renovant\core\acl\ACL', 'pdo');
		$RefProp->setAccessible(true);
		$pdo = $RefProp->getValue($ACL);
		$this->assertEquals('mysql', $pdo);
	}
	/**
	 * @depends testInit
	 */
	function testAuth() {
		$AUTH = sys::auth();
		$this->assertInstanceOf(Auth::class, $AUTH);
	}

	/**
	 * @depends testInfo
	 */
	function testAutoload() {
		sys::autoload('renovant\core\util\Date');
		$this->assertTrue(class_exists('renovant\core\util\Date', false));
	}

	/**
	 * @depends testInit
	 */
	function testCache() {
		$this->assertInstanceOf('renovant\core\cache\CacheInterface', sys::cache('sys'));
		$this->assertInstanceOf('renovant\core\cache\CacheInterface', sys::cache());
	}

	/**
	 * @depends testInit
	 * @throws ContextException
	 * @throws EventDispatcherException
	 * @throws \ReflectionException
	 */
	function testCmd() {
		sys::cache('sys')->delete('sys.CmdManager');
		$CmdManager = sys::cmd();
		$this->assertInstanceOf(CmdManager::class, $CmdManager);
	}

	/**
	 * @depends testInit
	 */
	function testPdo() {
		$this->assertInstanceOf('renovant\core\db\PDO', sys::pdo('mysql'));
	}

	/**
	 * @depends testInit
	 */
	function testPdoException() {
		try {
			sys::pdo('WRONG');
			$this->fail('Expected PDOException not thrown');
		} catch(\PDOException $Ex) {
			$this->assertEquals(0, $Ex->getCode());
			$this->assertMatchesRegularExpression('/valid data source name/', $Ex->getMessage());
		}
	}

	/**
	 * @depends testInit
	 * @throws ContextException
	 * @throws EventDispatcherException
	 * @throws SysException
	 * @throws \ReflectionException
	 */
	function testDispatchCLI() {
		$routes = [
			'CMD'			=> [ 'cmd' => 'console',		'namespace' => 'test.console' ],
			'SYS'			=> [ 'cmd' => 'sys',			'namespace' => 'renovant.core.bin' ]
		];

		$_SERVER['argv'] = [
			0 => \renovant\core\CLI_BOOTSTRAP,
			1 => 'console',
			2 => 'mod1',
			3 => 'foo',
			4 => '--bar=2'
		];
		$this->assertNull(sys::dispatchCLI($routes));
	}

	/**
	 * @depends testInit
	 * @throws EventDispatcherException
	 * @throws ContextException|\ReflectionException
	 */
	function testDispatchHTTP() {
		$_SERVER['SERVER_ADDR'] = 'example.com';
		$_SERVER['SERVER_PORT'] = 443;
		$_SERVER['REQUEST_URI'] = '/api/bar/';
		$this->assertNull(sys::dispatchHTTP('APP', self::HTTP_ROUTES));
	}
}
