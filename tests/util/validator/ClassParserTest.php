<?php
namespace test\util\validator;
use metadigit\core\util\validator\ClassParser;

class ClassParserTest extends \PHPUnit_Framework_TestCase {

	function testParse() {
		$metadata = (new ClassParser)->parse('mock\util\validator\Class1');
		$this->assertCount(3, $metadata);
		// check properties constraints
		$props = $metadata['properties'];
		$this->assertArrayHasKey('id', $props);
		$this->assertEquals(['min'=>5, 'max'=>15], $props['id']);
		$this->assertArrayHasKey('active', $props);
		$this->assertEquals(['true'=>true], $props['active']);
		// check null
		$nulls = $metadata['null'];
		$this->assertContains('email2', $nulls);
	}
}
