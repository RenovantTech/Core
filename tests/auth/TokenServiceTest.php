<?php
namespace test\auth;
use renovant\core\sys,
	renovant\core\auth\TokenService,
	renovant\core\auth\provider\PdoProvider;

class TokenServiceTest extends \PHPUnit\Framework\TestCase {

	static function setUpBeforeClass():void {
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS sys_users_auth;
			DROP TABLE IF EXISTS sys_users_tokens;
			DROP TABLE IF EXISTS sys_users;
		');
		new PdoProvider('mysql');
		sys::pdo('mysql')->exec('
			INSERT INTO sys_users (name, surname, email) VALUES ("John", "Red", "john.red@gmail.com");
			INSERT INTO sys_users (name, surname, email) VALUES ("Matt", "Brown", "matt.brown@gmail.com");
			INSERT INTO sys_users (name, surname, email) VALUES ("Dick", "Dastardly", "dick.dastardly@gmail.com");
			UPDATE sys_users_auth SET active = 1, login = "john.red", password = "'.password_hash('ABC123', PASSWORD_DEFAULT).'" WHERE user_id = 1;
			UPDATE sys_users_auth SET active = 0, login = "matt.brown", password = "'.password_hash('DEF456', PASSWORD_DEFAULT).'" WHERE user_id = 2;
			UPDATE sys_users_auth SET active = 1, login = "dick.dastardly", password = "'.password_hash('GHI789', PASSWORD_DEFAULT).'" WHERE user_id = 3;
		');
	}

	static function tearDownAfterClass():void {
		sys::pdo('mysql')->exec('
--			DROP TABLE IF EXISTS sys_users_auth;
--			DROP TABLE IF EXISTS sys_users_tokens;
--			DROP TABLE IF EXISTS sys_users;
		');
	}

	/**
	 * @return TokenService
	 * @throws \ReflectionException
	 * @throws \renovant\core\context\ContextException
	 * @throws \renovant\core\event\EventDispatcherException
	 */
	function testConstruct() {
		$TokenService = new TokenService;
		$this->assertInstanceOf(TokenService::class, $TokenService);
		/** @var $TokenService $AuthService */
		$TokenService = sys::context()->get('sys.AuthTokenService');
		$this->assertInstanceOf(TokenService::class, $TokenService);
		return $TokenService;
	}

	/**
	 * @depends testConstruct
	 * @param TokenService $TokenService
	 * @return string
	 * @throws \Exception
	 */
	function testSetActivateUserToken(TokenService $TokenService) {
		$token = $TokenService->setActivateUserToken(3);
		$this->assertEquals(64, strlen($token));
		$dbToken = sys::pdo('mysql')->query('SELECT token FROM sys_users_tokens WHERE type = "'.TokenService::TOKEN_ACTIVATE_USER.'" AND user_id = 3 AND expire >= NOW()')->fetchColumn();
		$this->assertEquals($token, $dbToken);
		return $token;
	}

	/**
	 * @depends testConstruct
	 * @depends testSetActivateUserToken
	 * @param TokenService $TokenService
	 * @param string $token
	 */
	function testCheckActivateUserToken(TokenService $TokenService, string $token) {
		// false token
		$this->assertEquals(0, $TokenService->checkActivateUserToken('f43hth34th34ht'));
		// true token
		$userID = $TokenService->checkActivateUserToken($token);
		$this->assertEquals(3, $userID);
	}

	/**
	 * @depends testConstruct
	 * @param TokenService $TokenService
	 * @return string
	 * @throws \Exception
	 */
	function testSetResetEmailToken(TokenService $TokenService) {
		$token = $TokenService->setResetEmailToken(3, 'dick.dastardly@yahoo.com');
		$this->assertEquals(64, strlen($token));
		$dbToken = sys::pdo('mysql')->query('SELECT token FROM sys_users_tokens WHERE type = "'.TokenService::TOKEN_RESET_EMAIL.'" AND user_id = 3 AND expire >= NOW()')->fetchColumn();
		$this->assertEquals($token, $dbToken);
		$newEmail = sys::pdo('mysql')->query('SELECT data FROM sys_users_tokens WHERE type = "'.TokenService::TOKEN_RESET_EMAIL.'" AND user_id = 3 AND expire >= NOW()')->fetchColumn();
		$this->assertEquals('dick.dastardly@yahoo.com', $newEmail);
		return $token;
	}

	/**
	 * @depends testConstruct
	 * @depends testSetResetEmailToken
	 * @param TokenService $TokenService
	 * @param string $token
	 */
	function testCheckResetEmailToken(TokenService $TokenService, string $token) {
		// false token
		$this->assertEquals(0, $TokenService->checkResetEmailToken('f43hth34th34ht'));
		// true token
		$userID = $TokenService->checkResetEmailToken($token);
		$this->assertEquals(3, $userID);
		$email = sys::pdo('mysql')->query('SELECT login FROM sys_users_auth WHERE user_id = 3')->fetchColumn();
		$this->assertEquals('dick.dastardly@yahoo.com', $email);
		// try 2 shot
		$this->assertEquals(0, $TokenService->checkResetEmailToken($token));
	}

	/**
	 * @depends testConstruct
	 * @param TokenService $TokenService
	 * @return string
	 * @throws \Exception
	 */
	function testSetResetPwdToken(TokenService $TokenService) {
		$token = $TokenService->setResetPwdToken(3);
		$this->assertEquals(64, strlen($token));
		$dbToken = sys::pdo('mysql')->query('SELECT token FROM sys_users_tokens WHERE type = "'.TokenService::TOKEN_RESET_PWD.'" AND user_id = 3 AND expire >= NOW()')->fetchColumn();
		$this->assertEquals($token, $dbToken);
		return $token;
	}

	/**
	 * @depends testConstruct
	 * @depends testSetResetPwdToken
	 * @param TokenService $TokenService
	 * @param string $token
	 */
	function testCheckResetPwdToken(TokenService $TokenService, string $token) {
		// false token
		$this->assertEquals(0, $TokenService->checkResetPwdToken('f43hth34th34ht'));
		// true token
		$this->assertEquals(3, $TokenService->checkResetPwdToken($token));
		// try 2 shot
		$this->assertEquals(0, $TokenService->checkResetPwdToken($token));
	}
}
