<?php
namespace test\auth;
use metadigit\core\auth\provider\ProviderInterface;
use metadigit\core\sys,
	metadigit\core\auth\AUTH,
	metadigit\core\auth\Exception,
	metadigit\core\http\Event,
	metadigit\core\http\Request,
	metadigit\core\http\Response,
	test\auth\provider\PdoProviderTest;

class AUTHTest extends \PHPUnit\Framework\TestCase {

	static function setUpBeforeClass() {
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS `sys_auth`;
			DROP TABLE IF EXISTS `sys_tokens`;
			DROP TABLE IF EXISTS `sys_users`;
		');
	}

	static function tearDownAfterClass() {
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS `sys_auth`;
			DROP TABLE IF EXISTS `sys_tokens`;
			DROP TABLE IF EXISTS `sys_users`;
		');
	}

	/**
	 * @return AUTH
	 */
	function testConstruct() {
		$AUTH = new AUTH;
		$this->assertInstanceOf(AUTH::class, $AUTH);
		return $AUTH;
	}

	function testConstructException() {
		try {
			new AUTH('INVALID');
			$this->fail('Expected Exception not thrown');
		} catch(Exception $Ex) {
			$this->assertEquals(1, $Ex->getCode());
			$this->assertRegExp('/INVALID/', $Ex->getMessage());
		}
	}

	/**
	 * @depends testConstruct
	 * @param AUTH $AUTH
	 */
	function testProvider(AUTH $AUTH) {
		PdoProviderTest::setUpBeforeClass();
		$this->assertInstanceOf(ProviderInterface::class, $AUTH->provider());
		sys::pdo('mysql')->exec('
			INSERT INTO sys_users (name, surname, email) VALUES ("John", "Red", "john.red@gmail.com");
			UPDATE sys_auth SET active = 1, login = "john.red", password = "'.password_hash('ABC123', PASSWORD_DEFAULT).'" WHERE user_id = 1;
		');
	}

	/**
	 * @depends testConstruct
	 * @throws \metadigit\core\context\ContextException
	 * @throws \metadigit\core\event\EventDispatcherException
	 */
	function testInit() {
		$AUTH = sys::context()->get('sys.AUTH');
		session_start();
		$_SESSION['__AUTH__']['foo'] = 'bar';
		$_SERVER['REQUEST_URI'] = '/';
		$AUTH->init(new Event(new Request, new Response));
		$this->assertEquals('bar', $AUTH->get('foo'));
		session_destroy();
	}

	/**
	 * @depends testConstruct
	 * @throws \metadigit\core\context\ContextException
	 * @throws \metadigit\core\event\EventDispatcherException
	 */
	function testInitException() {
		try {
			$AUTH = sys::context()->get('sys.AUTH');
			$_SERVER['REQUEST_URI'] = '/';
			$AUTH->init(new Event(new Request, new Response));
			$this->fail('Expected Exception not thrown');
		} catch(Exception $Ex) {
			$this->assertEquals(23, $Ex->getCode());
			$this->assertRegExp('/must be already started/', $Ex->getMessage());
		}
	}

	/**
	 * @depends testConstruct
	 * @param AUTH $AUTH
	 */
	function testSet(AUTH $AUTH) {
		$AUTH->set('foo', 'bar');
		$this->assertEquals('bar', $AUTH->get('foo'));
	}

	/**
	 * @depends testConstruct
	 * @param AUTH $AUTH
	 */
	function testGet(AUTH $AUTH) {
		$AUTH->set('foo', 'bar');
		$AUTH->set('color', 'red');
		$this->assertEquals('bar', $AUTH->get('foo'));
		$this->assertEquals('red', $AUTH->get('color'));
		$this->assertEquals(['foo'=>'bar', 'color'=>'red'], $AUTH->get());
	}

	/**
	 * @depends testConstruct
	 * @param AUTH $AUTH
	 */
	function testGID(AUTH $AUTH) {
		$AUTH->set('GID', 1);
		$this->assertEquals(1, $AUTH->GID());
	}

	/**
	 * @depends testConstruct
	 * @param AUTH $AUTH
	 */
	function testGROUP(AUTH $AUTH) {
		$AUTH->set('GROUP', 'admin');
		$this->assertEquals('admin', $AUTH->GROUP());
	}

	/**
	 * @depends testConstruct
	 * @param AUTH $AUTH
	 */
	function testNAME(AUTH $AUTH) {
		$AUTH->set('NAME', 'John Red');
		$this->assertEquals('John Red', $AUTH->NAME());
	}

	/**
	 * @depends testConstruct
	 * @param AUTH $AUTH
	 */
	function testUID(AUTH $AUTH) {
		$AUTH->set('UID', 1);
		$this->assertEquals(1, $AUTH->UID());
	}

	/**
	 * @depends testConstruct
	 * @param AUTH $AUTH
	 */
	function testCommit(AUTH $AUTH) {
		$AUTH->set('foo', 'bar');
		$AUTH->commit();
		$this->assertEquals('bar', $_SESSION['__AUTH__']['foo']);
	}
}
