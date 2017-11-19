<?php
namespace test\log\writer;
use metadigit\core\log\writer\FileWriter;

class FileWriterTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		// 1) full path
		$Writer = new FileWriter(\metadigit\core\TMP_DIR.'test.log');
		$this->assertInstanceOf('metadigit\core\log\writer\FileWriter', $Writer);
		$this->assertFileExists(\metadigit\core\TMP_DIR.'test.log');
		unlink(\metadigit\core\TMP_DIR.'test.log');
		// 2) only file name
		$Writer = new FileWriter('filewriter.log');
		$this->assertInstanceOf('metadigit\core\log\writer\FileWriter', $Writer);
		$this->assertFileExists(\metadigit\core\LOG_DIR.'filewriter.log');
		return $Writer;
	}

	/**
	 * @depends testConstructor
	 */
	function testWrite(FileWriter $Writer) {
		$time = time();
		$Writer->write($time, 'test message DEBUG', LOG_DEBUG);
		$Writer->write($time, 'test message INFO');
		$Writer->write($time, 'test message WARNING', LOG_WARNING);
		$Writer->write($time, 'test message EMERG', LOG_EMERG, 'kernel');
		$this->assertFileExists(\metadigit\core\LOG_DIR.'filewriter.log');
		$lines = file(\metadigit\core\LOG_DIR.'filewriter.log', FILE_IGNORE_NEW_LINES);
		$this->assertStringEndsWith('[DEBUG] test message DEBUG', $lines[0]);
		$this->assertStringEndsWith('[INFO] test message INFO', $lines[1]);
		$this->assertStringEndsWith('[WARNING] test message WARNING', $lines[2]);
		$this->assertStringEndsWith('[EMERG] kernel: test message EMERG', $lines[3]);
	}
}
