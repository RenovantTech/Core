<?php
namespace test\auth;
use renovant\core\sys,
	renovant\core\auth\Auth,
	renovant\core\auth\AuthService,
	renovant\core\auth\AuthServiceJWT,
	renovant\core\auth\provider\PdoProvider,
	renovant\core\http\Event,
	renovant\core\http\Request,
	renovant\core\http\Response;

class AuthServiceJWTTest extends \PHPUnit\Framework\TestCase {

	static function setUpBeforeClass():void {
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS sys_authz_rules;
			DROP TABLE IF EXISTS sys_authz_maps;
			DROP TABLE IF EXISTS sys_authz;
			DROP TABLE IF EXISTS sys_users_2_roles;
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
			DROP TABLE IF EXISTS sys_authz_rules;
			DROP TABLE IF EXISTS sys_authz_maps;
			DROP TABLE IF EXISTS sys_authz;
			DROP TABLE IF EXISTS sys_users_2_roles;
			DROP TABLE IF EXISTS sys_users_auth;
			DROP TABLE IF EXISTS sys_users_tokens;
			DROP TABLE IF EXISTS sys_users;
		');
	}

	/**
	 * @return AuthServiceJWT
	 * @throws \ReflectionException
	 * @throws \renovant\core\context\ContextException
	 * @throws \renovant\core\event\EventDispatcherException
	 */
	function testConstruct() {
		$AuthService = new AuthServiceJWT;
		$this->assertInstanceOf(AuthServiceJWT::class, $AuthService);
		/** @var AuthServiceJWT $AuthService */
		$AuthService = sys::context()->get('sys.AUTH');
		$this->assertInstanceOf(AuthServiceJWT::class, $AuthService);
		return $AuthService;
	}

	/**
	 * @depends testConstruct
	 * @param AuthServiceJWT $AuthService
	 * @throws \ReflectionException
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
	 * @throws \renovant\core\auth\AuthException|\ReflectionException
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
		$this->assertEquals(null, $Auth->data('foo'));
		session_destroy();
	}

	/**
	 * @depends testConstruct
	 * @param AuthServiceJWT $AuthService
	 */
	function testCheckCredentials(AuthServiceJWT $AuthService) {
		$this->assertEquals(AuthService::LOGIN_UNKNOWN, $AuthService->checkCredentials('jack.green', '123456'));
		$this->assertEquals(AuthService::LOGIN_DISABLED, $AuthService->checkCredentials('matt.brown', '123456'));
		$this->assertEquals(AuthService::LOGIN_PWD_INVALID, $AuthService->checkCredentials('john.red', '123456'));
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
}
