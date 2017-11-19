<?php
namespace test\log\writer;
use function metadigit\core\pdo;
use metadigit\core\log\writer\SqliteWriter;

class SqliteWriterTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$Writer = new SqliteWriter('sqlite','log');
		$this->assertInstanceOf('metadigit\core\log\writer\SqliteWriter', $Writer);
		return $Writer;
	}

	/**
	 * @depends testConstructor
	 */
	function testWrite(SqliteWriter $Writer) {
		$time = time();
		$Writer->write($time, 'test message INFO');
		$Writer->write($time, 'test message WARNING', LOG_WARNING);
		$Writer->write($time, 'test message EMERG', LOG_EMERG, 'kernel');
		unset($Writer);
		$pdostm = pdo('sqlite')->query('SELECT * FROM `log`', \PDO::FETCH_ASSOC);

		$row = $pdostm->fetch();
		$this->assertEquals('INFO', $row['level']);
		$this->assertEquals(null, $row['facility']);
		$this->assertEquals('test message INFO', $row['message']);

		$row = $pdostm->fetch();
		$this->assertEquals('WARNING', $row['level']);
		$this->assertEquals(null, $row['facility']);
		$this->assertEquals('test message WARNING', $row['message']);

		$row = $pdostm->fetch();
		$this->assertEquals('EMERG', $row['level']);
		$this->assertEquals('kernel', $row['facility']);
		$this->assertEquals('test message EMERG', $row['message']);
	}
}
