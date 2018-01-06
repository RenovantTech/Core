<?php
namespace test\container;
use metadigit\core\container\ContainerException,
	metadigit\core\container\ContainerYamlParser;

class ContainerYamlParserTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @throws ContainerException
	 */
	function testParseNamespace() {
		$maps = ContainerYamlParser::parseNamespace('test.container');
		$this->assertCount(2, $maps['id2class']);
		$this->assertArrayHasKey('test.container.Mock1', $maps['id2class']);
		$this->assertEquals(['test\container\Mock1'], $maps['id2class']['test.container.Mock1']);
		$this->assertCount(2, $maps['class2id']);
		$this->assertArrayHasKey('test\container\Mock1', $maps['class2id']);
		$this->assertEquals(['test.container.Mock1'], $maps['class2id']['test\container\Mock1']);
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
