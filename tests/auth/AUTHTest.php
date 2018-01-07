<?php
namespace test\auth;
use metadigit\core\auth\AUTH,
	metadigit\core\auth\AuthException;

class AUTHTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @return AUTH
	 */
	function testConstruct() {
		session_start();
		$AUTH = new AUTH;
		$this->assertInstanceOf(AUTH::class, $AUTH);
		return $AUTH;
	}

	function testConstructException() {
		try {
			new AUTH('INVALID');
			$this->fail('Expected Exception not thrown');
		} catch(AuthException $Ex) {
			$this->assertEquals(1, $Ex->getCode());
			$this->assertRegExp('/INVALID/', $Ex->getMessage());
		}

		try {
			session_destroy();
			new AUTH('SESSION');
			$this->fail('Expected Exception not thrown');
		} catch(AuthException $Ex) {
			$this->assertEquals(13, $Ex->getCode());
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
