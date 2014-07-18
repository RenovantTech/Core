<?php
namespace test\util\csv;
use metadigit\core\util\csv\CsvWriter;

class CsvWriterTest extends \PHPUnit_Framework_TestCase {

	function testConstructor() {
		$CsvWriter = new CsvWriter();
		$this->assertInstanceOf('metadigit\core\util\csv\CsvWriter', $CsvWriter);
	}

	/**
	 * @depends testConstructor
	 */
	function testSetData() {
		$CsvWriter = new CsvWriter();
		// array data
		$data = [
			['name'=>'John', 'surname'=>'Red', 'age'=>23],
			['name'=>'Robert', 'surname'=>'Brown', 'age'=>18],
			['name'=>'Alistar', 'surname'=>'Green', 'age'=>24]
		];
		$CsvWriter->setData($data);
		$ReflProp = new \ReflectionProperty('metadigit\core\util\csv\CsvWriter', '_data');
		$ReflProp->setAccessible(true);
		$_data = $ReflProp->getValue($CsvWriter);
		$this->assertCount(3, $_data);
		$ReflProp = new \ReflectionProperty('metadigit\core\util\csv\CsvWriter', 'iteratorMode');
		$ReflProp->setAccessible(true);
		$iteratorMode = $ReflProp->getValue($CsvWriter);
		$this->assertSame(CsvWriter::ITERATE_ARRAY, $iteratorMode);
		// objects data
		$data = [
			new \ArrayObject(['name'=>'John', 'surname'=>'Red', 'age'=>23]),
			new \ArrayObject(['name'=>'Robert', 'surname'=>'Brown', 'age'=>18]),
			new \ArrayObject(['name'=>'Alistar', 'surname'=>'Green', 'age'=>24])
		];
		$CsvWriter->setData($data);
		$ReflProp = new \ReflectionProperty('metadigit\core\util\csv\CsvWriter', '_data');
		$ReflProp->setAccessible(true);
		$_data = $ReflProp->getValue($CsvWriter);
		$this->assertCount(3, $_data);
		$ReflProp = new \ReflectionProperty('metadigit\core\util\csv\CsvWriter', 'iteratorMode');
		$ReflProp->setAccessible(true);
		$iteratorMode = $ReflProp->getValue($CsvWriter);
		$this->assertSame(CsvWriter::ITERATE_OBJECT, $iteratorMode);
	}

	/**
	 * @depends testConstructor
	 */
	function testAddColumn() {
		$CsvWriter = new CsvWriter();
		$CsvWriter->addColumn('Name', 'name', 'strtoupper')
			->addColumn('Surname', 'surnam', 'strtoupper');

		$ReflProp = new \ReflectionProperty('metadigit\core\util\csv\CsvWriter', '_labels');
		$ReflProp->setAccessible(true);
		$_labels = $ReflProp->getValue($CsvWriter);
		$this->assertCount(2, $_labels);
		$this->assertSame('Surname', $_labels[1]);

		$ReflProp = new \ReflectionProperty('metadigit\core\util\csv\CsvWriter', '_indexes');
		$ReflProp->setAccessible(true);
		$_indexes = $ReflProp->getValue($CsvWriter);
		$this->assertCount(2, $_indexes);
		$this->assertSame('surnam', $_indexes[1]);

		$ReflProp = new \ReflectionProperty('metadigit\core\util\csv\CsvWriter', '_callbacks');
		$ReflProp->setAccessible(true);
		$_callbacks = $ReflProp->getValue($CsvWriter);
		$this->assertCount(2, $_callbacks);
		$this->assertSame('strtoupper', $_callbacks[1]);
	}

	/**
	 * @depends testConstructor
	 */
	function testWrite() {
		$CsvWriter = new CsvWriter();
		$data = [
			['name'=>'John', 'surname'=>'Red', 'age'=>23],
			['name'=>'Robert', 'surname'=>'Brown', 'age'=>18],
			['name'=>'Alistar', 'surname'=>'Green', 'age'=>24]
		];
		$CsvWriter->setData($data);
		$CsvWriter->addColumn('Name', 'name', 'strtoupper')
			->addColumn('Surname', 'surname', 'strtoupper')
			->addColumn('Age', 'age');

		ob_start();
		$CsvWriter->write('php://output');
		$array = explode(chr(10),ob_get_clean());
		$this->assertCount(5, $array);
		$this->assertSame('Age', str_getcsv($array[0])[2]);
		$this->assertSame('GREEN', str_getcsv($array[3])[1]);
	}
}