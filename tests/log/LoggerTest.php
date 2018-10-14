<?php
namespace test\log;
use renovant\core\log\Logger,
	renovant\core\log\writer\FileWriter;

class LoggerTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$Logger = new Logger;
		$this->assertInstanceOf('renovant\core\log\Logger', $Logger);
		return $Logger;
	}

	/**
	 * @depends testConstructor
	 * @param Logger $Logger
	 */
	function testLogLevel(Logger $Logger) {
		$FileWriter = new FileWriter(\renovant\core\LOG_DIR.'test.INFO.log');
		$Logger->addWriter($FileWriter, LOG_INFO);
		$Logger->log('0 test INFO', LOG_INFO);
		$Logger->log('1 test WARNING', LOG_WARNING);
		$Logger->log('2 SOPPRESSED', LOG_DEBUG);
		$Logger->log('2 test EMERG', LOG_EMERG);
		$Logger->flush();
		$lines = file(\renovant\core\LOG_DIR.'test.INFO.log', FILE_IGNORE_NEW_LINES);
		$this->assertStringEndsWith('[EMERG] 2 test EMERG', array_pop($lines));
		$this->assertStringEndsWith('[WARNING] 1 test WARNING', array_pop($lines));
		$this->assertStringEndsWith('[INFO] 0 test INFO', array_pop($lines));
	}

	/**
	 * @depends testConstructor
	 * @param Logger $Logger
	 */
	function testLogFacility(Logger $Logger) {
		$FileWriter = new FileWriter(\renovant\core\LOG_DIR.'test.auth.log');
		$Logger->addWriter($FileWriter, LOG_INFO, 'auth');
		$Logger->log('0 test', LOG_INFO, 'auth');
		$Logger->log('1 test', LOG_INFO, 'auth');
		$Logger->log('2 SOPPRESSED', LOG_INFO, 'invalid');
		$Logger->log('2 test', LOG_INFO, 'auth');
		$Logger->flush();
		$lines = file(\renovant\core\LOG_DIR.'test.auth.log', FILE_IGNORE_NEW_LINES);
		$this->assertStringEndsWith('[INFO] auth: 2 test', array_pop($lines));
		$this->assertStringEndsWith('[INFO] auth: 1 test', array_pop($lines));
		$this->assertStringEndsWith('[INFO] auth: 0 test', array_pop($lines));
	}
}
