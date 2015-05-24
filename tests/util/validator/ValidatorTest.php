<?php
namespace test\util\validator;
use metadigit\core\util\validator\Validator;

class ValidatorTest extends \PHPUnit_Framework_TestCase {

	function testValidate() {
		$Object = new \mock\util\validator\Class1;
		$this->assertCount(7, Validator::validate($Object));

		// min & max
		$Object->id = 3;
		$this->assertEquals('min', Validator::validate($Object)['id']);
		$Object->id = 18;
		$this->assertEquals('max', Validator::validate($Object)['id']);
		$Object->id = 6;
		$this->assertArrayNotHasKey('id', Validator::validate($Object));

		// boolean
		$Object->active = true;
		$this->assertArrayNotHasKey('active', Validator::validate($Object));

		// minLength & maxLength
		$Object->name = 'John';
		$this->assertArrayNotHasKey('name', Validator::validate($Object));

		$Object->surname = 'Dalton';
		$this->assertArrayNotHasKey('surname', Validator::validate($Object));

		// email (NOT null, NOT empty)
		$Object->email1 = 'test@';
		$this->assertArrayHasKey('email1', Validator::validate($Object));
		$Object->email1 = '';
		$this->assertArrayHasKey('email1', Validator::validate($Object));
		$Object->email1 = null;
		$this->assertArrayHasKey('email1', Validator::validate($Object));
		$Object->email1 = 'test@example.com';
		$this->assertArrayNotHasKey('email1', Validator::validate($Object));

		// email (null)
		$Object->email2 = '';
		$this->assertArrayHasKey('email2', Validator::validate($Object));
		$Object->email2 = null;
		$this->assertArrayNotHasKey('email2', Validator::validate($Object));

		// email (empty)
		$Object->email3 = null;
		$this->assertArrayNotHasKey('email3', Validator::validate($Object));
		$Object->email3 = '';
		$this->assertArrayNotHasKey('email3', Validator::validate($Object));

		// date (NOT null)
		$Object->date1 = '2015';
		$this->assertArrayHasKey('date1', Validator::validate($Object));
		$Object->date1 = '';
		$this->assertArrayHasKey('date1', Validator::validate($Object));
		$Object->date1 = null;
		$this->assertArrayHasKey('date1', Validator::validate($Object));
		$Object->date1 = '2015-03-31';
		$this->assertArrayNotHasKey('date1', Validator::validate($Object));

		// date (null)
		$Object->date2 = '2015';
		$this->assertArrayHasKey('date2', Validator::validate($Object));
		$Object->date2 = '';
		$this->assertArrayHasKey('date2', Validator::validate($Object));
		$Object->date2 = null;
		$this->assertArrayNotHasKey('date2', Validator::validate($Object));
		$Object->date2 = '2015-03-31';
		$this->assertArrayNotHasKey('date2', Validator::validate($Object));

		// datetime
		$Object->datetime = '2015';
		$this->assertArrayHasKey('datetime', Validator::validate($Object));
		$Object->datetime = '';
		$this->assertArrayHasKey('datetime', Validator::validate($Object));
		$Object->datetime = null;
		$this->assertArrayHasKey('datetime', Validator::validate($Object));
		$Object->datetime = '2015-03-31 12:34:25';
		$this->assertArrayNotHasKey('datetime', Validator::validate($Object));

		// VALID
		$this->assertCount(0, Validator::validate($Object));
	}
}
