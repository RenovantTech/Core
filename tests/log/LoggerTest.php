<?php
namespace test\log;
use metadigit\core\log\Logger,
	metadigit\core\log\writer\FileWriter;

class LoggerTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$Logger = new Logger;
		$this->assertInstanceOf('metadigit\core\log\Logger', $Logger);
		return $Logger;
	}

	/**
	 * @depends testConstructor
	 */
	function testLogLevel(Logger $Logger) {
		$FileWriter = new FileWriter(\metadigit\core\LOG_DIR.'test.INFO.log');
		$Logger->addWriter($FileWriter, LOG_INFO);

		$Logger->log('0 test INFO', LOG_INFO);
		$Logger->log('1 test WARNING', LOG_WARNING);
		$Logger->log('2 SOPPRESSED', LOG_DEBUG);
		$Logger->log('2 test EMERG', LOG_EMERG);

		$lines = file(\metadigit\core\LOG_DIR.'test.INFO.log', FILE_IGNORE_NEW_LINES);

		$this->assertStringEndsWith('[INFO] 0 test INFO', $lines[0]);
		$this->assertStringEndsWith('[WARNING] 1 test WARNING', $lines[1]);
		$this->assertStringEndsWith('[EMERG] 2 test EMERG', $lines[2]);
	}

	/**
	 * @depends testConstructor
	 */
	function testLogFacility(Logger $Logger) {
		$FileWriter = new FileWriter(\metadigit\core\LOG_DIR.'test.auth.log');
		$Logger->addWriter($FileWriter, LOG_INFO, 'auth');

		$Logger->log('0 test', LOG_INFO, 'auth');
		$Logger->log('1 test', LOG_INFO, 'auth');
		$Logger->log('2 SOPPRESSED', LOG_INFO, 'invalid');
		$Logger->log('2 test', LOG_INFO, 'auth');

		$lines = file(\metadigit\core\LOG_DIR.'test.auth.log', FILE_IGNORE_NEW_LINES);

		$this->assertStringEndsWith('[INFO] auth: 0 test', $lines[0]);
		$this->assertStringEndsWith('[INFO] auth: 1 test', $lines[1]);
		$this->assertStringEndsWith('[INFO] auth: 2 test', $lines[2]);
	}
}
