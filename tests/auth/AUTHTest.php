<?php
namespace test\auth;
use metadigit\core\sys;

class AUTHTest extends \PHPUnit\Framework\TestCase {

	function testHelper() {
		$AUTH = sys::auth();
		$this->assertInstanceOf('metadigit\core\auth\AUTH', $AUTH->init());
	}
}
