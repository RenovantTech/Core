<?php
namespace test\container;
use metadigit\core\container\ContainerException,
	metadigit\core\container\ContainerYamlParser;

class ContainerYamlParserTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @throws ContainerException
	 */
	function testParseNamespace() {
		list($id2classMap, $class2idMap) = ContainerYamlParser::parseNamespace('test.container');
		$this->assertCount(2, $id2classMap);
		$this->assertArrayHasKey('test.container.Mock1', $id2classMap);
		$this->assertEquals(['test\container\Mock1'], $id2classMap['test.container.Mock1']);
		$this->assertCount(2, $class2idMap);
		$this->assertArrayHasKey('test\container\Mock1', $class2idMap);
		$this->assertEquals(['test.container.Mock1'], $class2idMap['test\container\Mock1']);
	}

	function testParseNamespaceException() {
		try {
			ContainerYamlParser::parseNamespace('test.xxxx');
			$this->fail('Expected ContainerException not thrown');
		} catch(ContainerException $Ex) {
			$this->assertEquals(11, $Ex->getCode());
			$this->assertRegExp('/YAML config file NOT FOUND/', $Ex->getMessage());
		}
	}
}
