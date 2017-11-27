<?php
namespace test\util\excel;
use metadigit\core\util\excel\ExcelWriter;

class ExcelWriterTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$ExcelWriter = new ExcelWriter();
		$this->assertInstanceOf('metadigit\core\util\excel\ExcelWriter', $ExcelWriter);
	}

	/**
	 * @depends testConstructor
	 */
	function testSetData() {
		$ExcelWriter = new ExcelWriter();
		// array data
		$data = [
			['name'=>'John', 'surname'=>'Red', 'age'=>23],
			['name'=>'Robert', 'surname'=>'Brown', 'age'=>18],
			['name'=>'Alistar', 'surname'=>'Green', 'age'=>24]
		];
		$ExcelWriter->setData($data);
		$ReflProp = new \ReflectionProperty('metadigit\core\util\excel\ExcelWriter', '_data');
		$ReflProp->setAccessible(true);
		$_data = $ReflProp->getValue($ExcelWriter);
		$this->assertCount(3, $_data);
		$ReflProp = new \ReflectionProperty('metadigit\core\util\excel\ExcelWriter', 'iteratorMode');
		$ReflProp->setAccessible(true);
		$iteratorMode = $ReflProp->getValue($ExcelWriter);
		$this->assertSame(ExcelWriter::ITERATE_ARRAY, $iteratorMode);
		// objects data
		$data = [
			new \ArrayObject(['name'=>'John', 'surname'=>'Red', 'age'=>23]),
			new \ArrayObject(['name'=>'Robert', 'surname'=>'Brown', 'age'=>18]),
			new \ArrayObject(['name'=>'Alistar', 'surname'=>'Green', 'age'=>24])
		];
		$ExcelWriter->setData($data);
		$ReflProp = new \ReflectionProperty('metadigit\core\util\excel\ExcelWriter', '_data');
		$ReflProp->setAccessible(true);
		$_data = $ReflProp->getValue($ExcelWriter);
		$this->assertCount(3, $_data);
		$ReflProp = new \ReflectionProperty('metadigit\core\util\excel\ExcelWriter', 'iteratorMode');
		$ReflProp->setAccessible(true);
		$iteratorMode = $ReflProp->getValue($ExcelWriter);
		$this->assertSame(ExcelWriter::ITERATE_OBJECT, $iteratorMode);
	}

	/**
	 * @depends testConstructor
	 */
	function testAddColumn() {
		$ExcelWriter = new ExcelWriter();
		$ExcelWriter->addColumn('Name', 'name', 'strtoupper')
			->addColumn('Surname', 'surnam', 'strtoupper');

		$ReflProp = new \ReflectionProperty('metadigit\core\util\excel\ExcelWriter', '_labels');
		$ReflProp->setAccessible(true);
		$_labels = $ReflProp->getValue($ExcelWriter);
		$this->assertCount(2, $_labels);
		$this->assertSame('Surname', $_labels[1]);

		$ReflProp = new \ReflectionProperty('metadigit\core\util\excel\ExcelWriter', '_indexes');
		$ReflProp->setAccessible(true);
		$_indexes = $ReflProp->getValue($ExcelWriter);
		$this->assertCount(2, $_indexes);
		$this->assertSame('surnam', $_indexes[1]);

		$ReflProp = new \ReflectionProperty('metadigit\core\util\excel\ExcelWriter', '_callbacks');
		$ReflProp->setAccessible(true);
		$_callbacks = $ReflProp->getValue($ExcelWriter);
		$this->assertCount(2, $_callbacks);
		$this->assertSame('strtoupper', $_callbacks[1]);
	}

	/**
	 * @depends testConstructor
	 */
	function testWrite() {
		$ExcelWriter = new ExcelWriter();
		$data = [
			['name'=>'John', 'surname'=>'Red', 'age'=>23],
			['name'=>'Robert', 'surname'=>'Brown', 'age'=>18],
			['name'=>'Alistar', 'surname'=>'Green', 'age'=>24]
		];
		$ExcelWriter->setData($data);
		$ExcelWriter->addColumn('Name', 'name', 'strtoupper')
			->addColumn('Surname', 'surname', 'strtoupper')
			->addColumn('Age', 'age');

		ob_start();
		$ExcelWriter->write('php://output');
		$XML = new \SimpleXMLElement(str_replace('nowrap','',ob_get_clean()));
		$this->assertSame('Age', (string) $XML->tr[0]->th[2]);
		$this->assertSame('GREEN', (string) $XML->tr[3]->td[1]);
	}
}
