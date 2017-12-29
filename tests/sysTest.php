<?php
namespace test;
use metadigit\core\sys,
	metadigit\core\sysboot,
	metadigit\core\console\CmdManager;

class sysTest extends \PHPUnit\Framework\TestCase {

	function testConstants() {
		$this->assertEquals('3.0.0', \metadigit\core\VERSION);
		$this->assertEquals(realpath(__DIR__.'/../src/'), \metadigit\core\DIR);
	}

	/**
	 * @throws \metadigit\core\util\yaml\YamlException
	 */
	function testBoot() {
		list($Sys, $namespaces) = sysBoot::boot();

		$this->assertArrayHasKey('metadigit\core', $namespaces);
		$this->assertEquals(realpath(__DIR__.'/../src'), $namespaces['metadigit\core']);
		$this->assertArrayHasKey('test', $namespaces);
		$this->assertEquals(__DIR__, realpath($namespaces['test']));

		// APPS HTTP/CLI
		$ReflProp = new \ReflectionProperty('metadigit\core\sys', 'cnfApps');
		$ReflProp->setAccessible(true);
		$apps = $ReflProp->getValue($Sys);
		$this->assertArrayHasKey('webconsole', $apps['HTTP']);
		$this->assertEquals(8080,					$apps['HTTP']['webconsole']['httpPort']);
		$this->assertEquals('/',					$apps['HTTP']['webconsole']['baseUrl']);
		$this->assertEquals('metadigit.webconsole',	$apps['HTTP']['webconsole']['namespace']);
		$this->assertArrayHasKey('CP', $apps['HTTP']);
		$this->assertEquals(80,					$apps['HTTP']['CP']['httpPort']);
		$this->assertEquals('/ControlPanel/',	$apps['HTTP']['CP']['baseUrl']);
		$this->assertEquals('project.cp',		$apps['HTTP']['CP']['namespace']);
		$this->assertCount(1, $apps['CLI']);
//@TODO		$this->assertEquals('metadigit.webconsole',	$apps['CLI']['webconsole']);
		$this->assertEquals('test.console',			$apps['CLI']['console']);

		// constants
		$ReflProp = new \ReflectionProperty('metadigit\core\sys', 'cnfConstants');
		$ReflProp->setAccessible(true);
		$constants = $ReflProp->getValue($Sys);
		$this->assertArrayHasKey('ASSETS_DIR', $constants);
		$this->assertEquals('/var/www/devel.com/data/assets/', $constants['ASSETS_DIR']);

		// settings
		$ReflProp = new \ReflectionProperty('metadigit\core\sys', 'cnfSettings');
		$ReflProp->setAccessible(true);
		$settings = $ReflProp->getValue($Sys);
		$this->assertArrayHasKey('timeZone', $settings);
		$this->assertEquals('Europe/London', $settings['timeZone']);

		// ACL service
		$ReflProp = new \ReflectionProperty('metadigit\core\sys', 'cnfAcl');
		$ReflProp->setAccessible(true);
		$acl = $ReflProp->getValue($Sys);
		$this->assertArrayHasKey('orm', $acl);
		$this->assertEquals(true, $acl['orm']);

		// Cache service
		$ReflProp = new \ReflectionProperty('metadigit\core\sys', 'cnfCache');
		$ReflProp->setAccessible(true);
		$caches = $ReflProp->getValue($Sys);
		$this->assertArrayHasKey('sys', $caches);
		$this->assertEquals('metadigit\core\cache\SqliteCache', $caches['sys']['class']);

		// DB service
		$ReflProp = new \ReflectionProperty('metadigit\core\sys', 'cnfPdo');
		$ReflProp->setAccessible(true);
		$pdo = $ReflProp->getValue($Sys);
		$this->assertArrayHasKey('sys-cache', $pdo);
		$this->assertEquals('sqlite:/tmp/metadigit-core/cache/sys-cache.sqlite', $pdo['sys-cache']['dns']);

		// LOG service
		$ReflProp = new \ReflectionProperty('metadigit\core\sys', 'cnfLog');
		$ReflProp->setAccessible(true);
		$log = $ReflProp->getValue($Sys);
		$this->assertArrayHasKey('kernel', $log);
		$this->assertEquals('LOG_INFO', $log['kernel']['level']);
	}

	/**
	 * @depends testBoot
	 * @throws \metadigit\core\util\yaml\YamlException
	 */
	function testInit() {
		sys::init();
		$this->assertTrue(file_exists(sys::CACHE_FILE));
	}

	function testInfo() {
		// test PHP namespaces
		$this->assertEquals('metadigit\core\http', sys::info('metadigit\core\http\Dispatcher', sys::INFO_NAMESPACE));
		$this->assertEquals('Dispatcher', sys::info('metadigit\core\http\Dispatcher', sys::INFO_CLASS));
		$this->assertEquals(realpath(__DIR__.'/../src/http').'/Dispatcher', sys::info('metadigit\core\http\Dispatcher', sys::INFO_PATH));
		$this->assertEquals(realpath(__DIR__.'/../src/http'), sys::info('metadigit\core\http\Dispatcher', sys::INFO_PATH_DIR));
		$this->assertEquals('Dispatcher', sys::info('metadigit\core\http\Dispatcher', sys::INFO_PATH_FILE));
		list($namespace, $className, $dir, $file) = sys::info('metadigit\core\http\Dispatcher');
		$this->assertEquals('metadigit\core\http', $namespace);
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
		$this->assertEquals(TEST_DIR.'//app/Dispatcher', sys::info('test.app.Dispatcher', sys::INFO_PATH));
		$this->assertEquals(TEST_DIR.'//app', sys::info('test.app.Dispatcher', sys::INFO_PATH_DIR));
		$this->assertEquals('Dispatcher', sys::info('test.app.Dispatcher', sys::INFO_PATH_FILE));
		list($namespace, $className, $dir, $file) = sys::info('test.app.Dispatcher');
		$this->assertEquals('test\app', $namespace);
		$this->assertEquals('Dispatcher', $className);
		$this->assertEquals(TEST_DIR.'//app', $dir);
		$this->assertEquals('Dispatcher', $file);
	}

	/**
	 * @depends testInit
	 */
	function testAcl() {
		$ACL = sys::acl();
		$this->assertInstanceOf('metadigit\core\acl\ACL', $ACL);
		$this->assertTrue(\metadigit\core\ACL_ROUTES);
		$this->assertTrue(\metadigit\core\ACL_OBJECTS);
		$this->assertTrue(\metadigit\core\ACL_ORM);
		$RefProp = new \ReflectionProperty('metadigit\core\acl\ACL', 'pdo');
		$RefProp->setAccessible(true);
		$pdo = $RefProp->getValue($ACL);
		$this->assertEquals('mysql', $pdo);
	}

	/**
	 * @depends testInfo
	 */
	function testAutoload() {
		sys::autoload('metadigit\core\util\Date');
		$this->assertTrue(class_exists('metadigit\core\util\Date', false));
	}

	/**
	 * @depends testInit
	 */
	function testCache() {
		$this->assertInstanceOf('metadigit\core\cache\CacheInterface', sys::cache('sys'));
	}

	/**
	 * @depends testInit
	 */
	function testCmd() {
		$CmdManager = sys::cmd();
		$this->assertInstanceOf(CmdManager::class, $CmdManager);
	}

	/**
	 * @depends testInit
	 */
	function testPdo() {
		$this->assertInstanceOf('metadigit\core\db\PDO', sys::pdo('sys-cache'));
		$this->assertInstanceOf('metadigit\core\db\PDO', sys::pdo('mysql'));
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
			$this->assertRegExp('/invalid data source name/', $Ex->getMessage());
		}
	}

	/**
	 * @depends testInit
	 */
	function _testDispatchCLI() {
		$_SERVER['argv'] = [
			0 => \metadigit\core\CLI_BOOTSTRAP,
			1 => 'console',
			2 => 'mod1',
			3 => 'foo',
			4 => '--bar=2'
		];
		try {
			sys::dispatch('cli');
		} catch(\Exception $Ex) {
			$msg = $Ex->getMessage();
			$this->assertEquals('Europe/London', $msg);
		}
	}

	/**
	 * @depends testInit
	 */
	function _testDispatchHTTP() {
		$_SERVER['REQUEST_URI'] = '/';
		$_SERVER['SERVER_PORT'] = '80';
		sys::dispatch('http');
		//$this->assertEquals('Europe/London', $r);
	}
}
