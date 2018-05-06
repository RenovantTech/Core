<?php
namespace test\http;
use metadigit\core\sys,
	metadigit\core\http\Session;

class SessionTest extends \PHPUnit\Framework\TestCase {

	static function setUpBeforeClass() {
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS sys_sessions;
		');
	}

	static function tearDownAfterClass() {
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS sys_sessions;
		');
	}

	function testConstruct() {
		session_start();
		$Session = new Session;
		$this->assertInstanceOf('metadigit\core\http\Session', $Session);
		return $Session;
	}

	/**
	 * @depends testConstruct
	 * @param Session $Session
	 */
	function testSet(Session $Session) {
		$Session->name = 'Jack';
		$this->assertEquals('Jack', $_SESSION['default']['name']);
		$Session->surname = 'Brown';
		$this->assertEquals('Brown', $_SESSION['default']['surname']);

		$Session2 = new Session('foo');
		$Session2->name = 'John';
		$this->assertEquals('John', $_SESSION['foo']['name']);
		$Session2->foo_bar = 'foo_bar';
		$this->assertEquals('foo_bar', $_SESSION['foo']['foo_bar']);
	}

	/**
	 * @depends testConstruct
	 * @param Session $Session
	 */
	function testIsset(Session $Session) {
		$Session->name = 'Jack';
		$this->assertTrue(isset($Session->name));
	}

	/**
	 * @depends testConstruct
	 * @param Session $Session
	 */
	function testGet(Session $Session) {
		$Session->name = 'Jack';
		$this->assertEquals('Jack', $Session->name);
	}

	function ____testSetExpirationSeconds() {

	}

	function testSetExpirationHops() {
		$ReflProperty = new \ReflectionProperty('metadigit\core\http\Session', '_expiringData');
		$ReflProperty->setAccessible(true);
		$ReflMethod1 = new \ReflectionMethod('metadigit\core\http\Session', 'expireData');
		$ReflMethod1->setAccessible(true);
		$ReflMethod2 = new \ReflectionMethod('metadigit\core\http\Session', 'expireGlobalData');
		$ReflMethod2->setAccessible(true);
		$Session = new Session('red');
		$Session->foo = 'Foo';
		$Session->bar = 'Bar';
		$Session->setExpirationHops(2);
		// before Hop
		$this->assertEquals(2, $_SESSION['_METADATA_']['red']['ENGH']);
		$this->assertEquals('Foo', $_SESSION['red']['foo']);
		$this->assertEquals('Foo', $Session->foo);
		// simulate Hop 1
		$ReflProperty->setValue([]);
		$ReflMethod1->invoke($Session);
		$ReflMethod2->invoke($Session);
		$this->assertEquals(1, $_SESSION['_METADATA_']['red']['ENGH']);
		$this->assertEquals('Foo', $_SESSION['red']['foo']);
		$this->assertEquals('Foo', $Session->foo);
		// simulate Hop 2
		$ReflProperty->setValue([]);
		$ReflMethod1->invoke($Session);
		$ReflMethod2->invoke($Session);
		$this->assertEquals(0, $_SESSION['_METADATA_']['red']['ENGH']);
		$this->assertFalse(isset($_SESSION['red']['foo']));
		$this->assertEquals('Foo', $Session->foo);
		// simulate Hop 3
		$ReflProperty->setValue([]);
		$ReflMethod1->invoke($Session);
		$ReflMethod2->invoke($Session);
		$this->assertFalse(isset($_SESSION['_METADATA_']['red']['ENGH']));
		$this->assertFalse(isset($_SESSION['red']['foo']));
		$this->assertNull($Session->foo);
	}
}
