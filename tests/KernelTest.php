<?php
namespace test;
use function metadigit\core\{acl, cache, pdo};
use metadigit\core\Kernel;

class KernelTest extends \PHPUnit\Framework\TestCase {

	function testConstants() {
		$this->assertEquals('3.0.0', \metadigit\core\VERSION);
		$this->assertEquals(realpath(__DIR__.'/../src/'), \metadigit\core\DIR);
	}

	function testInit() {
//		Kernel::init();
		$ReflProp = new \ReflectionProperty('metadigit\core\Kernel', 'apps');
		$ReflProp->setAccessible(true);
		$apps = $ReflProp->getValue();
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

		$ReflProp = new \ReflectionProperty('metadigit\core\Kernel', 'namespaces');
		$ReflProp->setAccessible(true);
		$namespaces = $ReflProp->getValue();
		$this->assertArrayHasKey('metadigit\core', $namespaces);
		$this->assertEquals(realpath(__DIR__.'/../src/'), $namespaces['metadigit\core']);

		$ReflProp = new \ReflectionProperty('metadigit\core\Kernel', 'settings');
		$ReflProp->setAccessible(true);
		$settings = $ReflProp->getValue();
		$this->assertArrayHasKey('timeZone', $settings);
		$this->assertEquals('Europe/London', $settings['timeZone']);

		$ReflProp = new \ReflectionProperty('metadigit\core\Kernel', 'cacheConf');
		$ReflProp->setAccessible(true);
		$cacheConf = $ReflProp->getValue();
		$this->assertArrayHasKey('kernel', $cacheConf);
		$this->assertEquals('metadigit\core\cache\SqliteCache', $cacheConf['kernel']['class']);

		$ReflProp = new \ReflectionProperty('metadigit\core\Kernel', 'pdoConf');
		$ReflProp->setAccessible(true);
		$pdoConf = $ReflProp->getValue();
		$this->assertArrayHasKey('kernel-cache', $pdoConf);
		$this->assertEquals('sqlite:/tmp/metadigit-core/cache/kernel-cache.sqlite', $pdoConf['kernel-cache']['dns']);
	}

	/**
	 * @depends testInit
	 */
	function testParseClassName() {
		list($namespace, $className, $dir, $file) = Kernel::parseClassName('metadigit\core\http\Dispatcher');
		$this->assertEquals('metadigit\core\http', $namespace);
		$this->assertEquals('Dispatcher', $className);
		$this->assertEquals(realpath(__DIR__.'/../src/http'), $dir);
		$this->assertEquals('Dispatcher', $file);
	}

	/**
	 * @depends testInit
	 */
	function testAcl() {
		$ACL = acl();
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
	 * @depends testInit
	 */
	function testCache() {
		$this->assertInstanceOf('metadigit\core\cache\CacheInterface', cache('kernel'));
		$this->assertInstanceOf('metadigit\core\cache\CacheInterface', cache('system'));
	}

	/**
	 * @depends testInit
	 */
	function testPdo() {
		$this->assertInstanceOf('metadigit\core\db\PDO', pdo('kernel-cache'));
		$this->assertInstanceOf('metadigit\core\db\PDO', pdo('mysql'));
	}

	/**
	 * @depends testInit
	 */
	function testLog() {
		file_put_contents(\metadigit\core\LOG_DIR.'kernel.log', '');
		Kernel::log('kernel message', LOG_INFO, 'kernel');
		Kernel::logFlush();
		$lines = file(\metadigit\core\LOG_DIR.'kernel.log', FILE_IGNORE_NEW_LINES);
		$this->assertStringEndsWith('[INFO] kernel: kernel bootstrap', $lines[0]);
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
			Kernel::dispatch('cli');
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
		Kernel::dispatch('http');
		//$this->assertEquals('Europe/London', $r);
	}
}
