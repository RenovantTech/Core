<?php
namespace test\auth;
use renovant\core\auth\provider\ProviderInterface;
use renovant\core\sys,
	renovant\core\auth\Auth,
	renovant\core\auth\AuthService,
	renovant\core\auth\Exception,
	renovant\core\http\Event,
	renovant\core\http\Request,
	renovant\core\http\Response;

class AuthServiceTest extends \PHPUnit\Framework\TestCase {

	static function setUpBeforeClass():void {
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS `sys_auth`;
			DROP TABLE IF EXISTS `sys_tokens`;
			DROP TABLE IF EXISTS `sys_users`;
		');
		Auth::erase();
	}

	static function tearDownAfterClass():void {
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS `sys_auth`;
			DROP TABLE IF EXISTS `sys_tokens`;
			DROP TABLE IF EXISTS `sys_users`;
		');
		Auth::erase();
	}

	/**
	 * @return AuthService
	 */
	function testConstruct() {
		$AuthService = new AuthService;
		$this->assertInstanceOf(AuthService::class, $AuthService);
		return $AuthService;
	}

	function testConstructException() {
		try {
			new AuthService('INVALID');
			$this->fail('Expected Exception not thrown');
		} catch(Exception $Ex) {
			$this->assertEquals(1, $Ex->getCode());
			$this->assertMatchesRegularExpression('/INVALID/', $Ex->getMessage());
		}
	}

	/**
	 * @depends testConstruct
	 * @param AuthService $AuthService
	 */
	function testProvider(AuthService $AuthService) {
		self::setUpBeforeClass();
		$this->assertInstanceOf(ProviderInterface::class, $AuthService->provider());
		sys::pdo('mysql')->exec('
			INSERT INTO sys_users (name, surname, email) VALUES ("John", "Red", "john.red@gmail.com");
			UPDATE sys_auth SET active = 1, login = "john.red", password = "'.password_hash('ABC123', PASSWORD_DEFAULT).'" WHERE user_id = 1;
		');
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
	 * @param AuthService $AuthService
	 * @throws \Exception
	 */
	function testCommit(AuthService $AuthService) {
//		$AuthService->set('foo', 'bar');
		$AuthService->commit();
		$this->assertEquals('bar', $_SESSION['__AUTH__']['foo']);
	}
}
