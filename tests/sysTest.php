<?php
namespace test;
use metadigit\core\sys,
	metadigit\core\sysboot;
use const metadigit\core\TMP_DIR;

class sysTest extends \PHPUnit\Framework\TestCase {

	function testConstants() {
		$this->assertEquals('3.0.0', \metadigit\core\VERSION);
		$this->assertEquals(realpath(__DIR__.'/../src/'), \metadigit\core\DIR);
	}

	function testBoot() {
		list($Sys, $namespaces) = sysboot::boot();

		$this->assertArrayHasKey('metadigit\core', $namespaces);
		$this->assertEquals(realpath(__DIR__.'/../src/'), $namespaces['metadigit\core']);

		$ReflProp = new \ReflectionProperty('metadigit\core\sys', 'apps');
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
		$this->assertEquals('mock.console',			$apps['CLI']['console']);

		$ReflProp = new \ReflectionProperty('metadigit\core\sys', 'settings');
		$ReflProp->setAccessible(true);
		$settings = $ReflProp->getValue($Sys);
		$this->assertArrayHasKey('timeZone', $settings);
		$this->assertEquals('Europe/London', $settings['timeZone']);

		$ReflProp = new \ReflectionProperty('metadigit\core\sys', 'cache');
		$ReflProp->setAccessible(true);
		$caches = $ReflProp->getValue($Sys);
		$this->assertArrayHasKey('sys', $caches);
		$this->assertEquals('metadigit\core\cache\SqliteCache', $caches['sys']['class']);

		$ReflProp = new \ReflectionProperty('metadigit\core\sys', 'pdo');
		$ReflProp->setAccessible(true);
		$pdo = $ReflProp->getValue($Sys);
		$this->assertArrayHasKey('sys-cache', $pdo);
		$this->assertEquals('sqlite:/tmp/metadigit-core/cache/sys-cache.sqlite', $pdo['sys-cache']['dns']);
	}

	/**
	 * @depends testBoot
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
		$this->assertEquals('mock\app', sys::info('mock.app.Dispatcher', sys::INFO_NAMESPACE));
		$this->assertEquals('Dispatcher', sys::info('mock.app.Dispatcher', sys::INFO_CLASS));
		$this->assertEquals(MOCK_DIR.'/app/Dispatcher', sys::info('mock.app.Dispatcher', sys::INFO_PATH));
		$this->assertEquals(MOCK_DIR.'/app', sys::info('mock.app.Dispatcher', sys::INFO_PATH_DIR));
		$this->assertEquals('Dispatcher', sys::info('mock.app.Dispatcher', sys::INFO_PATH_FILE));
		list($namespace, $className, $dir, $file) = sys::info('mock.app.Dispatcher');
		$this->assertEquals('mock\app', $namespace);
		$this->assertEquals('Dispatcher', $className);
		$this->assertEquals(MOCK_DIR.'/app', $dir);
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
		$ReflProp = new \ReflectionProperty('metadigit\core\acl\ACL', 'pdo');
		$ReflProp->setAccessible(true);
		$pdo = $ReflProp->getValue($ACL);
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
		$this->assertInstanceOf('metadigit\core\cache\CacheInterface', sys::cache('system'));
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
	function testLog() {
		file_put_contents(\metadigit\core\LOG_DIR.'kernel.log', '');
		sys::log('kernel message', LOG_INFO, 'kernel');
		sys::shutdown();
		$lines = file(\metadigit\core\LOG_DIR.'kernel.log', FILE_IGNORE_NEW_LINES);
		$this->assertStringEndsWith('[INFO] kernel: sys bootstrap', $lines[0]);
	}

	/**
	 * @depends testLogger
	 */
	function _testDispatchCLI() {
		$_SERVER['argv'] = [
			0 => \metadigit\core\BOOTSTRAP,
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
	 * @depends testLogger
	 */
	function _testDispatchHTTP() {
		$_SERVER['REQUEST_URI'] = '/';
		$_SERVER['SERVER_PORT'] = '80';
		sys::dispatch('http');
		//$this->assertEquals('Europe/London', $r);
	}
}
