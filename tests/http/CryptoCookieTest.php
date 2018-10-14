<?php
namespace test\http;
use renovant\core\CoreProxy;
use renovant\core\http\CryptoCookie;

class CryptoCookieTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @return CryptoCookie
	 */
	function testConstruct() {
		$Cookie = new CryptoCookie('CRYPTO-COOKIE');
		$this->assertInstanceOf(CryptoCookie::class, $Cookie);

		$RefProp = new \ReflectionProperty(CryptoCookie::class, '_key');
		$RefProp->setAccessible(true);
		$key = $RefProp->getValue($Cookie);
		$this->assertEquals(32, strlen($key));

		return $Cookie;
	}

	/**
	 * @depends testConstruct
	 * @param CryptoCookie $Cookie
	 * @throws \renovant\core\http\Exception
	 */
	function testReadAndWrite(CryptoCookie $Cookie) {
		$data ='ASHFERUFFKJFSKJFDSF';
		$Cookie->write($data);
		$this->assertEquals($data, $Cookie->read());

		$data = [ 'foo'=>12, 'bar'=>32 ];
		$Cookie->write($data);
		$this->assertEquals($data, $Cookie->read());

		$Obj = new CoreProxy('test');
		$Cookie->write($Obj);
		$this->assertInstanceOf(CoreProxy::class, $Cookie->read());
	}
}
