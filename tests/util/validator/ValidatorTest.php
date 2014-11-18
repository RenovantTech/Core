<?php
namespace test\util\validator;
use metadigit\core\util\validator\Validator;

class ValidatorTest extends \PHPUnit_Framework_TestCase {

	function testParse() {
		$Object = new \mock\util\validator\Class1;
		$this->assertCount(4, Validator::validate($Object));
		$Object->id = 6;
		$this->assertCount(3, Validator::validate($Object));
		$this->assertArrayNotHasKey('id', Validator::validate($Object));
		$Object->active = true;
		$this->assertCount(2, Validator::validate($Object));
		$this->assertArrayNotHasKey('active', Validator::validate($Object));
		$Object->name = 'John';
		$this->assertCount(1, Validator::validate($Object));
		$this->assertArrayNotHasKey('name', Validator::validate($Object));
		$Object->surname = 'Dalton';
		$this->assertCount(0, Validator::validate($Object));
		// check null
		$Object->email = 'test@';
		$this->assertCount(1, Validator::validate($Object));
		$this->assertArrayHasKey('email', Validator::validate($Object));
		$Object->email = 'test@example.com';
		$this->assertCount(0, Validator::validate($Object));
	}
}
