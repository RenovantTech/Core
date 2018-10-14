<?php
namespace test\http;
use renovant\core\http\Request;

class RequestTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$_SERVER['REQUEST_URI'] = '/mod1/action2';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_GET['id'] = 7;
		$_SERVER['HTTP_X_PROXY'] = 'Squid Proxy';
		$Request = new Request();
		$this->assertEquals(7, $Request->get('id'));
		$this->assertEquals('GET', $Request->getMethod());
		$this->assertEquals('Squid Proxy', $Request->getHeader('X-PROXY'));

		$Request = new Request('/mod1/action2', 'GET', ['id'=>7], ['X-PROXY'=>'Squid Proxy'], 'foo bar');
		$this->assertEquals(7, $Request->get('id'));
		$this->assertEquals('GET', $Request->getMethod());
		$this->assertEquals('Squid Proxy', $Request->getHeader('X-PROXY'));
		$this->assertEquals('foo bar', $Request->getRawData());

		// check JSON raw data conversion to params
		$Request = new Request('/mod1/action2', 'PUT', null, ['HTTP_CONTENT_TYPE'=>'application/json'], '{ "id": 7, "name": "foo" }');
		$this->assertEquals(7, $Request->get('id'));
		$this->assertEquals('foo', $Request->get('name'));
		$this->assertEquals('PUT', $Request->getMethod());
	}
}
