<?php
namespace test\util\yaml;
use metadigit\core\util\yaml\Yaml,
	metadigit\core\util\yaml\YamlException;

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
