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
			DROP TABLE IF EXISTS `sys_auth`;
			DROP TABLE IF EXISTS `users`;
		');
	}

	protected function setUp() {
	}

	function testConstructor() {
		$PdoProvider = new PdoProvider('mysql', 'sys_auth');
		$this->assertInstanceOf(PdoProvider::class, $PdoProvider);
		sys::pdo('mysql')->exec('
			INSERT INTO users (type, name, surname, email) VALUES ("admin", "John", "Red", "john.red@gmail.com");
			INSERT INTO users (type, name, surname, email) VALUES ("user", "Matt", "Brown", "matt.brown@gmail.com");
			UPDATE sys_auth SET active = 1, login = "john.red", password = "'.password_hash('ABC123', PASSWORD_DEFAULT).'" WHERE id = 1;
			UPDATE sys_auth SET active = 0, login = "matt.brown", password = "'.password_hash('DEF456', PASSWORD_DEFAULT).'" WHERE id = 2;
		');
		return $PdoProvider;
	}

	/**
	 * @depends testConstructor
	 * @param PdoProvider $PdoProvider
	 * @throws \metadigit\core\container\ContainerException
	 */
	function testAuthenticateById(PdoProvider $PdoProvider) {
		$AUTH = sys::auth();

		$this->assertTrue($PdoProvider->authenticateById(1, $AUTH));
		$this->assertEquals(1, $AUTH->UID());
		$this->assertEquals('john.red@gmail.com', $AUTH->get('email'));

		$this->assertFalse($PdoProvider->authenticateById(5, $AUTH));
	}

	/**
	 * @depends testConstructor
	 * @param PdoProvider $PdoProvider
	 */
	function testCheckCredentials(PdoProvider $PdoProvider) {
		$this->assertEquals(AUTH::LOGIN_UNKNOWN, $PdoProvider->checkCredentials('jack.green', '123456'));
		$this->assertEquals(AUTH::LOGIN_DISABLED, $PdoProvider->checkCredentials('matt.brown', '123456'));
		$this->assertEquals(AUTH::LOGIN_PWD_MISMATCH, $PdoProvider->checkCredentials('john.red', '123456'));
		$this->assertEquals(1, $PdoProvider->checkCredentials('john.red', 'ABC123'));
	}

	/**
	 * @depends testConstructor
	 * @param PdoProvider $PdoProvider
	 */
	function testAuthenticate(PdoProvider $PdoProvider) {
		$this->assertEquals(AUTH::LOGIN_UNKNOWN, $PdoProvider->authenticate('jack.green', '123456'));
		$this->assertEquals(AUTH::LOGIN_DISABLED, $PdoProvider->authenticate('matt.brown', '123456'));
		$this->assertEquals(AUTH::LOGIN_PWD_MISMATCH, $PdoProvider->authenticate('john.red', '123456'));
		$this->assertEquals(1, $PdoProvider->authenticate('john.red', 'ABC123'));
	}

	/**
	 * @depends testConstructor
	 * @param PdoProvider $PdoProvider
	 * @return PdoProvider
	 */
	function testSetRefreshToken(PdoProvider $PdoProvider) {
		$this->assertNull($PdoProvider->setRefreshToken(1, 'ABC123XYZ', time()+3600));
		$this->assertNull($PdoProvider->setRefreshToken(2, 'ABC123XYZ', time()-3600));
		return $PdoProvider;
	}

	/**
	 * @depends testSetRefreshToken
	 * @param PdoProvider $PdoProvider
	 */
	function testCheckRefreshToken(PdoProvider $PdoProvider) {
		$this->assertTrue($PdoProvider->checkRefreshToken(1, 'ABC123XYZ'));
		$this->assertFalse($PdoProvider->checkRefreshToken(1, '___123XYZ'));
		$this->assertFalse($PdoProvider->checkRefreshToken(2, 'ABC123XYZ'));
	}
}
