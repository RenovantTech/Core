<?php
namespace test\util\yaml;
use renovant\core\util\yaml\Yaml,
	renovant\core\util\yaml\YamlException;

class YamlTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @throws YamlException
	 */
	function testParseFile() {
		$yaml = Yaml::parseFile(__DIR__.'/context.yml');
		$this->assertCount(3, $yaml);
		$this->assertCount(2, $yaml['services']);
		$this->assertCount(2, $yaml['events']);

		// test section
		$yaml = Yaml::parseFile(__DIR__.'/context.yml', 'foo');
		$this->assertCount(2, $yaml);
		$this->assertEquals('foo1', $yaml[0]);
		$this->assertEquals('foo2-override', $yaml[1]);

		// test not-existing section
		$yaml = Yaml::parseFile(__DIR__.'/context.yml', 'xxxxxx');
		$this->assertNull($yaml);
	}

	/**
	 * @depends testParseFile
	 * @throws YamlException
	 */
	function testParseContext() {
		$yaml = Yaml::parseContext('test.util.yaml');
		$this->assertCount(3, $yaml);
		$this->assertArrayHasKey('foo', $yaml);
	}
}
