<?php
namespace test;
use metadigit\core\Kernel;

class KernelTest extends \PHPUnit_Framework_TestCase {

	function testConstants() {
		$this->assertEquals(realpath(__DIR__.'/../src/'), \metadigit\core\DIR);
	}

	function testInit() {
		$ReflProp = new \ReflectionProperty('metadigit\core\Kernel', 'apps');
		$ReflProp->setAccessible(true);
		$apps = $ReflProp->getValue();
		$this->assertArrayHasKey('-', $apps['HTTP']);
		$this->assertEquals(8080,					$apps['HTTP']['-']['httpPort']);
		$this->assertEquals('/',					$apps['HTTP']['-']['baseUrl']);
		$this->assertEquals('metadigit.webconsole',	$apps['HTTP']['-']['namespace']);
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

		$ReflProp = new \ReflectionProperty('metadigit\core\Kernel', 'dbConf');
		$ReflProp->setAccessible(true);
		$dbConf = $ReflProp->getValue();
		$this->assertArrayHasKey('kernel-cache', $dbConf);
		$this->assertEquals('sqlite:/tmp/metadigit-core/cache/kernel-cache.sqlite|null|null', $dbConf['kernel-cache']);
	}

	/**
	 * @depends testInit
	 */
	function testParseClassName() {
		list($namespace, $className, $dir, $file) = Kernel::parseClassName('metadigit\core\web\Dispatcher');
		$this->assertEquals('metadigit\core\web', $namespace);
		$this->assertEquals('Dispatcher', $className);
		$this->assertEquals(realpath(__DIR__.'/../src/web'), $dir);
		$this->assertEquals('Dispatcher', $file);
	}


	/**
	 * @depends testInit
	 */
	function testCache() {
		$ReflProp = new \ReflectionProperty('metadigit\core\Kernel', 'Cache');
		$ReflProp->setAccessible(true);
		$Cache = $ReflProp->getValue();
		$this->assertInstanceOf('metadigit\core\cache\CacheInterface', $Cache);
	}

	/**
	 * @depends testInit
	 */
	function testPdo() {
		$this->assertInstanceOf('PDO', Kernel::pdo('kernel-cache'));
		$this->assertInstanceOf('PDO', Kernel::pdo('mysql'));
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
		}
		$this->assertEquals('Europe/London', $msg);

	}

	/**
	 * @depends testLogger
	 */
	function _testDispatchHTTP() {
		$_SERVER['REQUEST_URI'] = '/';
		$_SERVER['SERVER_PORT'] = '80';
		Kernel::dispatch('http');
		$this->assertEquals('Europe/London', $r);
	}
}