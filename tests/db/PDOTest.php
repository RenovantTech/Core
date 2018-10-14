<?php
namespace test\db;
use renovant\core\db\PDO;

class PDOTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @return \renovant\core\db\PDO
	 */
	function testConstruct() {
		$PDO = new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname=phpunit', 'phpunit', 'phpunit');
		$this->assertInstanceOf('renovant\core\db\PDO', $PDO);
		return $PDO;
	}

	function testConstructException() {
		try {
			new PDO('WRONG');
			$this->fail('Expected PDOException not thrown');
		} catch(\PDOException $Ex) {
			$this->assertEquals(0, $Ex->getCode());
			$this->assertRegExp('/invalid data source name/', $Ex->getMessage());
		}

		try {
			new PDO('mysql:unix_socket=/run/mysqld/mysqld.sock;dbname=phpunit', 'WRONG', 'WRONG');
			$this->fail('Expected PDOException not thrown');
		} catch(\PDOException $Ex) {
			$this->assertEquals(1045, $Ex->getCode());
			$this->assertRegExp('/Access denied for user/', $Ex->getMessage());
		}
	}

	/**
	 * @depends testConstruct
	 * @param \renovant\core\db\PDO $PDO
	 */
	function testExec($PDO) {
		$this->assertEquals(0, $PDO->exec('SELECT 1'));

	}
}
