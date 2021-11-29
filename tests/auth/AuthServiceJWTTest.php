<?php
namespace test\auth;
use renovant\core\auth\provider\ProviderInterface;
use renovant\core\sys,
	renovant\core\auth\Auth,
	renovant\core\auth\AuthService,
	renovant\core\auth\AuthServiceJWT,
	renovant\core\auth\Exception,
	renovant\core\http\Event,
	renovant\core\http\Request,
	renovant\core\http\Response;

class AuthServiceJWTTest extends \PHPUnit\Framework\TestCase {

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

	/**
	 * @return AuthServiceJWT
	 */
	function testConstruct() {
		$AuthService = new AuthServiceJWT;
		$this->assertInstanceOf(AuthServiceJWT::class, $AuthService);
		return $AuthService;
	}

	function testConstructException() {
		try {
			new AuthServiceJWT('INVALID');
			$this->fail('Expected Exception not thrown');
		} catch(Exception $Ex) {
			$this->assertEquals(1, $Ex->getCode());
			$this->assertMatchesRegularExpression('/INVALID/', $Ex->getMessage());
		}
	}

	/**
	 * @depends testConstruct
	 * @param AuthServiceJWT $AuthService
	 */
	function testProvider(AuthServiceJWT $AuthService) {
		self::setUpBeforeClass();
		$this->assertInstanceOf(ProviderInterface::class, $AuthService->provider());
		sys::pdo('mysql')->exec('
			INSERT INTO sys_users (name, surname, email) VALUES ("John", "Red", "john.red@gmail.com");
			INSERT INTO sys_users (name, surname, email) VALUES ("Matt", "Brown", "matt.brown@gmail.com");
			INSERT INTO sys_users (name, surname, email) VALUES ("Dick", "Dastardly", "dick.dastardly@gmail.com");
			UPDATE sys_users_auth SET active = 1, login = "john.red", password = "'.password_hash('ABC123', PASSWORD_DEFAULT).'" WHERE user_id = 1;
			UPDATE sys_users_auth SET active = 0, login = "matt.brown", password = "'.password_hash('DEF456', PASSWORD_DEFAULT).'" WHERE user_id = 2;
			UPDATE sys_users_auth SET active = 1, login = "dick.dastardly", password = "'.password_hash('GHI789', PASSWORD_DEFAULT).'" WHERE user_id = 3;
		');
	}

	/**
	 * @depends testConstruct
	 * @param AuthServiceJWT $AuthService
	 */
	function testAuthenticate(AuthServiceJWT $AuthService) {
		$AuthService->authenticate(11, 100, 'John Black', 'admin', ['foo'=>'bar']);
		$Auth = Auth::instance();
		$this->assertEquals(11, $Auth->UID());
		$this->assertEquals(100, $Auth->GID());
		$this->assertEquals('John Black', $Auth->NAME());
		$this->assertEquals('admin', $Auth->GROUP());
		$this->assertEquals('bar', $Auth->data('foo'));
	}

	/**
	 * @depends testConstruct
	 * @param AuthServiceJWT $AuthService
	 * @throws \renovant\core\auth\AuthException
	 */
	function testAuthenticateById(AuthServiceJWT $AuthService) {
		$AuthService->authenticateById(3);
		$Auth = Auth::instance();
		$this->assertEquals(3, $Auth->UID());
		$this->assertEquals(null, $Auth->GID());
		$this->assertEquals('Dick Dastardly', $Auth->NAME());
		$this->assertEquals(null, $Auth->GROUP());
		$this->assertEquals('dick.dastardly@gmail.com', $Auth->data('email'));
	}

	/**
	 * @depends testConstruct
	 * @throws \renovant\core\context\ContextException
	 * @throws \renovant\core\event\EventDispatcherException
	 * @throws \ReflectionException
	 */
	function testInit() {
		$AuthService = sys::context()->get('sys.AUTH');
		session_start();
		$_SESSION['__AUTH__']['UID'] = 1;
		$_SESSION['__AUTH__']['foo'] = 'bar';
		$_SERVER['REQUEST_URI'] = '/';
		$AuthService->init(new Event(new Request, new Response));
		$Auth = Auth::instance();
		$this->assertEquals('bar', $Auth->data('foo'));
		session_destroy();
	}

	/**
	 * @depends testConstruct
	 * @throws \renovant\core\context\ContextException
	 * @throws \renovant\core\event\EventDispatcherException
	 * @throws \ReflectionException
	 */
	function testInitException() {
		try {
			$AuthService = sys::context()->get('sys.AUTH');
			unset($_SESSION);
			$_SERVER['REQUEST_URI'] = '/';
			$AuthService->init(new Event(new Request, new Response));
			$this->fail('Expected Exception not thrown');
		} catch(Exception $Ex) {
			$this->assertEquals(23, $Ex->getCode());
			$this->assertMatchesRegularExpression('/must be already started/', $Ex->getMessage());
		}
	}

	/**
	 * @depends testConstruct
	 * @param AuthServiceJWT $AuthService
	 */
	function testCheckCredentials(AuthServiceJWT $AuthService) {
		$this->assertEquals(AuthService::LOGIN_UNKNOWN, $AuthService->checkCredentials('jack.green', '123456'));
		$this->assertEquals(AuthService::LOGIN_DISABLED, $AuthService->checkCredentials('matt.brown', '123456'));
		$this->assertEquals(AuthService::LOGIN_PWD_MISMATCH, $AuthService->checkCredentials('john.red', '123456'));
		$this->assertEquals(1, $AuthService->checkCredentials('john.red', 'ABC123'));
	}

	/**
	 * @depends testConstruct
	 * @param AuthServiceJWT $AuthService
	 * @throws \Exception
	 */
	function testCommit(AuthServiceJWT $AuthService) {
//		$AuthService->set('foo', 'bar');
		$AuthService->commit();
		$this->assertEquals('bar', $_SESSION['__AUTH__']['foo']);
	}

	/**
	 * @depends testConstruct
	 * @param AuthServiceJWT $AuthService
	 * @throws \Exception
	 */
	function testSetPassword(AuthServiceJWT $AuthService) {
		// with verification
		$this->assertEquals(AuthService::SET_PWD_MISMATCH, $AuthService->setPassword(1, 'XYZ123', null, 'ABC123xxx'));
		$this->assertEquals(AuthService::SET_PWD_OK, $AuthService->setPassword(1, 'XYZ123', null, 'ABC123'));
		$storedPwd = sys::pdo('mysql')->query('SELECT password FROM sys_users_auth WHERE user_id = 1')->fetchColumn();
		$this->assertTrue(password_verify('XYZ123', $storedPwd));

		// without verification
		$this->assertEquals(1, $AuthService->setPassword(1, 'XYZ456'));
		$storedPwd = sys::pdo('mysql')->query('SELECT password FROM sys_users_auth WHERE user_id = 1')->fetchColumn();
		$this->assertTrue(password_verify('XYZ456', $storedPwd));
	}

	/**
	 * @depends testConstruct
	 * @param AuthServiceJWT $AuthService
	 * @return string
	 * @throws \Exception
	 */
	function testSetResetEmailToken(AuthServiceJWT $AuthService) {
		$token = $AuthService->setResetEmailToken(3, 'dick.dastardly@yahoo.com');
		$this->assertEquals(64, strlen($token));
		$dbToken = sys::pdo('mysql')->query('SELECT token FROM sys_users_tokens WHERE type = "RESET_EMAIL" AND user_id = 3 AND expire >= NOW()')->fetchColumn();
		$this->assertEquals($token, $dbToken);
		$newEmail = sys::pdo('mysql')->query('SELECT data FROM sys_users_tokens WHERE type = "RESET_EMAIL" AND user_id = 3 AND expire >= NOW()')->fetchColumn();
		$this->assertEquals('dick.dastardly@yahoo.com', $newEmail);
		return $token;
	}

	/**
	 * @depends testConstruct
	 * @depends testSetResetEmailToken
	 * @param AuthServiceJWT $AuthService
	 * @param string $token
	 */
	function testCheckResetEmailToken(AuthServiceJWT $AuthService, string $token) {
		// false token
		$this->assertEquals(0, $AuthService->checkResetEmailToken('f43hth34th34ht'));
		// true token
		$userID = $AuthService->checkResetEmailToken($token);
		$this->assertEquals(3, $userID);
		$email = sys::pdo('mysql')->query('SELECT login FROM sys_users_auth WHERE user_id = 3')->fetchColumn();
		$this->assertEquals('dick.dastardly@yahoo.com', $email);
		// try 2 shot
		$this->assertEquals(0, $AuthService->checkResetEmailToken($token));
	}

	/**
	 * @depends testConstruct
	 * @param AuthServiceJWT $AuthService
	 * @return string
	 * @throws \Exception
	 */
	function testSetResetPwdToken(AuthServiceJWT $AuthService) {
		$token = $AuthService->setResetPwdToken(3);
		$this->assertEquals(64, strlen($token));
		$dbToken = sys::pdo('mysql')->query('SELECT token FROM sys_users_tokens WHERE type = "RESET_PWD" AND user_id = 3 AND expire >= NOW()')->fetchColumn();
		$this->assertEquals($token, $dbToken);
		return $token;
	}

	/**
	 * @depends testConstruct
	 * @depends testSetResetPwdToken
	 * @param AuthServiceJWT $AuthService
	 * @param string $token
	 */
	function testCheckResetPwdToken(AuthServiceJWT $AuthService, string $token) {
		// false token
		$this->assertEquals(0, $AuthService->checkResetPwdToken('f43hth34th34ht'));
		// true token
		$this->assertEquals(3, $AuthService->checkResetPwdToken($token));
		// try 2 shot
		$this->assertEquals(0, $AuthService->checkResetPwdToken($token));
	}
}
