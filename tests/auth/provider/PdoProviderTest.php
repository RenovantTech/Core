<?php
namespace test\auth\provider;
use renovant\core\sys,
	renovant\core\auth\provider\PdoProvider;

class PdoProviderTest extends \PHPUnit\Framework\TestCase {

	static function setUpBeforeClass():void {
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS `sys_users_auth`;
			DROP TABLE IF EXISTS `sys_users_tokens`;
			DROP TABLE IF EXISTS `sys_users`;
		');
	}

	static function tearDownAfterClass():void {
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS `sys_users_auth`;
			DROP TABLE IF EXISTS `sys_users_tokens`;
			DROP TABLE IF EXISTS `sys_users`;
		');
	}

	function testConstructor() {
		$PdoProvider = new PdoProvider('mysql');
		$this->assertInstanceOf(PdoProvider::class, $PdoProvider);
		sys::pdo('mysql')->exec('
			INSERT INTO sys_users (name, surname, email) VALUES ("John", "Red", "john.red@gmail.com");
			INSERT INTO sys_users (name, surname, email) VALUES ("Matt", "Brown", "matt.brown@gmail.com");
			INSERT INTO sys_users (name, surname, email) VALUES ("Dick", "Dastardly", "dick.dastardly@gmail.com");
			UPDATE sys_users_auth SET active = 1, login = "john.red", password = "'.password_hash('ABC123', PASSWORD_DEFAULT).'" WHERE user_id = 1;
			UPDATE sys_users_auth SET active = 0, login = "matt.brown", password = "'.password_hash('DEF456', PASSWORD_DEFAULT).'" WHERE user_id = 2;
			UPDATE sys_users_auth SET active = 1, login = "dick.dastardly", password = "'.password_hash('GHI789', PASSWORD_DEFAULT).'" WHERE user_id = 3;
		');
		return $PdoProvider;
	}

	/**
	 * @depends testConstructor
	 * @param PdoProvider $PdoProvider
	 * @return PdoProvider
	 */
	function testSetRefreshToken(PdoProvider $PdoProvider) {
		$this->assertNull($PdoProvider->tokenSet('AUTH-REFRESH', 1, 'ABC123XYZ', null, time()+3600));
		$this->assertNull($PdoProvider->tokenSet('AUTH-REFRESH', 2, 'ABC456XYZ', null,  time()-3600));
		return $PdoProvider;
	}

	/**
	 * @depends testSetRefreshToken
	 * @param PdoProvider $PdoProvider
	 */
	function testCheckRefreshToken(PdoProvider $PdoProvider) {
		$this->assertTrue($PdoProvider->tokenCheck('AUTH-REFRESH', 'ABC123XYZ', 1));
		$this->assertFalse($PdoProvider->tokenCheck('AUTH-REFRESH', '___123XYZ', 1));
		$this->assertFalse($PdoProvider->tokenCheck('AUTH-REFRESH', 'ABC456XYZ', 2));
	}
}
