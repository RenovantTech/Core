<?php
namespace test;

use renovant\core\sysinfo;

class sysinfoTest extends \PHPUnit\Framework\TestCase {
	public function testNamespace() {
		$this->assertEquals('renovant\core\http', sysinfo::namespace('renovant\core\http\Dispatcher'));
		$this->assertEquals('test\app', sysinfo::namespace('test.app.Dispatcher'));
	}

	public function testClass() {
		$this->assertEquals('Dispatcher', sysinfo::class('renovant\core\http\Dispatcher'));
		$this->assertEquals('Dispatcher', sysinfo::class('test.app.Dispatcher'));
	}

	public function testPath() {
		$this->assertEquals(realpath(__DIR__ . '/../src/http') . '/Dispatcher', sysinfo::path('renovant\core\http\Dispatcher'));
		$this->assertEquals(TEST_DIR . '/app/Dispatcher', sysinfo::path('test.app.Dispatcher'));
	}

	public function testDir() {
		$this->assertEquals(realpath(__DIR__ . '/../src/http'), sysinfo::dir('renovant\core\http\Dispatcher'));
		$this->assertEquals(TEST_DIR . '/app', sysinfo::dir('test.app.Dispatcher'));
	}

	public function testFile() {
		$this->assertEquals('Dispatcher', sysinfo::file('renovant\core\http\Dispatcher'));
		$this->assertEquals('Dispatcher', sysinfo::file('test.app.Dispatcher'));
	}
}
