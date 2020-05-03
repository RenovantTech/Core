<?php
namespace test\container;
use renovant\core\container\ContainerException,
	renovant\core\container\ContainerYamlParser;
use renovant\core\CoreProxy;

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

		$this->assertCount(2, $maps['services']);

		$this->assertArrayHasKey('test.container.Mock1', $maps['services']);
		$this->assertEquals(Mock1::class, $maps['services']['test.container.Mock1']['class']);
		$this->assertCount(0, $maps['services']['test.container.Mock1']['constructor']);
		$this->assertCount(4, $maps['services']['test.container.Mock1']['properties']);
		$this->assertEquals([ 2, 5, 8 ], $maps['services']['test.container.Mock1']['properties']['numbers']);

		$this->assertArrayHasKey('test.container.Mock2', $maps['services']);
		$this->assertEquals(Mock2::class, $maps['services']['test.container.Mock2']['class']);
		$this->assertCount(2, $maps['services']['test.container.Mock2']['constructor']);
		$this->assertEquals('Mock2', $maps['services']['test.container.Mock2']['constructor'][0]);
		$this->assertInstanceOf(CoreProxy::class, $maps['services']['test.container.Mock2']['constructor'][1]);
		$this->assertCount(0, $maps['services']['test.container.Mock2']['properties']);
	}

	function testParseNamespaceException() {
		try {
			ContainerYamlParser::parseNamespace('test.xxxx');
			$this->fail('Expected ContainerException not thrown');
		} catch(ContainerException $Ex) {
			$this->assertEquals(11, $Ex->getCode());
			$this->assertMatchesRegularExpression('/YAML config file NOT FOUND/', $Ex->getMessage());
		}
	}
}
