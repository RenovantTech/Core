<?php
namespace test\auth\provider;
use metadigit\core\sys,
	metadigit\core\auth\AUTH,
	metadigit\core\auth\provider\PdoProvider;

class PdoProviderTest extends \PHPUnit\Framework\TestCase {

	static function setUpBeforeClass() {
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS `sys_auth`;
			DROP TABLE IF EXISTS `users`;
			CREATE TABLE IF NOT EXISTS `users` (
				id			INT UNSIGNED NOT NULL AUTO_INCREMENT,
				type		VARCHAR(20),
				name		VARCHAR(20),
				surname		VARCHAR(20),
				email		VARCHAR(30) NULL DEFAULT NULL,
				PRIMARY KEY(id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		');
	}

	static function tearDownAfterClass() {
		sys::pdo('mysql')->exec('
--			DROP TABLE IF EXISTS `sys_auth`;
--			DROP TABLE IF EXISTS `users`;
		');
	}

	protected function setUp() {
	}

	function testConstructor() {
		$PdoProvider = new PdoProvider('mysql', 'sys_auth');
		$this->assertInstanceOf(PdoProvider::class, $PdoProvider);
		return $PdoProvider;
	}

	/**
	 * @depends testConstructor
	 * @param PdoProvider $PdoProvider
	 */
	function testLogin(PdoProvider $PdoProvider) {
		sys::pdo('mysql')->exec('
			INSERT INTO users (type, name, surname, email) VALUES ("admin", "John", "Red", "john.red@gmail.com");
			INSERT INTO users (type, name, surname, email) VALUES ("user", "Matt", "Brown", "matt.brown@gmail.com");
			UPDATE sys_auth SET active = 1, login = "john.red", password = "'.password_hash('ABC123', PASSWORD_DEFAULT).'" WHERE id = 1;
			UPDATE sys_auth SET active = 0, login = "matt.brown", password = "'.password_hash('DEF456', PASSWORD_DEFAULT).'" WHERE id = 2;
		');
		$this->assertEquals(AUTH::LOGIN_UNKNOWN, $PdoProvider->login('jack.green', '123456'));
		$this->assertEquals(AUTH::LOGIN_DISABLED, $PdoProvider->login('matt.brown', '123456'));
		$this->assertEquals(AUTH::LOGIN_PWD_MISMATCH, $PdoProvider->login('john.red', '123456'));
		$this->assertEquals(1, $PdoProvider->login('john.red', 'ABC123'));
	}
}
