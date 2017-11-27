<?php
namespace test\log\writer;
use metadigit\core\log\writer\FileTreeWriter;

class FileTreeWriterTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$Writer = new FileTreeWriter('test.log');
		$this->assertInstanceOf('metadigit\core\log\writer\FileTreeWriter', $Writer);
		return $Writer;
	}

	/**
	 * @depends testConstructor
	 */
	function testWrite(FileTreeWriter $Writer) {
		$time = time();
		$Writer->write($time, 'test message DEBUG', LOG_DEBUG);
		$Writer->write($time, 'test message INFO');
		$Writer->write($time, 'test message WARNING', LOG_WARNING);
		$Writer->write($time, 'test message EMERG', LOG_EMERG, 'kernel');
		$this->assertFileExists(\metadigit\core\LOG_DIR.date('Y/m/d').'/test.log');
		$lines = file(\metadigit\core\LOG_DIR.date('Y/m/d').'/test.log', FILE_IGNORE_NEW_LINES);
		$this->assertStringEndsWith('[DEBUG] test message DEBUG', $lines[0]);
		$this->assertStringEndsWith('[INFO] test message INFO', $lines[1]);
		$this->assertStringEndsWith('[WARNING] test message WARNING', $lines[2]);
		$this->assertStringEndsWith('[EMERG] kernel: test message EMERG', $lines[3]);
	}
}
