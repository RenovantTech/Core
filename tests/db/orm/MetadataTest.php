<?php
namespace test\db\orm;
use metadigit\core\db\orm\Metadata;

class MetadataTest extends \PHPUnit_Framework_TestCase {

	function testParse1() {
		$Metadata = new Metadata('mock\db\orm\User');

		// data sources
		$this->assertEquals('users', $Metadata->sql('source'));
		$this->assertEquals('users', $Metadata->sql('target'));

		// primary keys
		$pkeys = $Metadata->pkeys();
		$this->assertCount(1, $pkeys);
		$this->assertContains('id', $pkeys);

		// criteria
		$criteria = $Metadata->criteria();
		$this->assertCount(2, $criteria);
		$this->assertArrayHasKey('activeAge', $criteria);
		$this->assertEquals('active,EQ,1|age,GTE,?1', $criteria['activeAge']);
		$this->assertArrayHasKey('dateMonth', $criteria);
		$this->assertEquals('YEAR(lastTime) = ?1 AND MONTH(lastTime) = ?2', $criteria['dateMonth']);

		// order-by
		$orderBy = $Metadata->order();
		$this->assertCount(1, $orderBy);
		$this->assertArrayHasKey('nameASC', $orderBy);
		$this->assertEquals('name ASC, surname ASC', $orderBy['nameASC']);

		// subsets
		$subsets = $Metadata->subset();
		$this->assertCount(3, $subsets);
		$this->assertArrayHasKey('mini', $subsets);
		$this->assertEquals(['id','name','score'], $subsets['mini']);
		$this->assertArrayHasKey('large', $subsets);
		$this->assertEquals(['id','active','name','age','score'], $subsets['large']);

		// properties
		$properties = $Metadata->properties();
		$this->assertCount(9, $properties);
		$this->assertArrayHasKey('id', $properties);
		$this->assertTrue($properties['id']['autoincrement']);
		$this->assertTrue($properties['id']['primarykey']);
		$this->assertArrayHasKey('name', $properties);
		$this->assertArrayHasKey('surname', $properties);
		$this->assertEquals('string', $properties['surname']['type']);
		$this->assertFalse($properties['surname']['null']);
		$this->assertArrayHasKey('age', $properties);
		$this->assertEquals('integer', $properties['age']['type']);
		$this->assertTrue($properties['age']['null']);
		$this->assertArrayHasKey('lastTime', $properties);
		$this->assertEquals('datetime', $properties['lastTime']['type']);
		$this->assertTrue($properties['lastTime']['null']);
	}

	function testParse2() {
		$Metadata = new Metadata('mock\db\orm\User2');
		// data sources
		$this->assertEquals('users', $Metadata->sql('source'));
		$this->assertEquals('users', $Metadata->sql('target'));
		$this->assertEquals('sp_people_insert, name, surname, age, score, @id', $Metadata->sql('insertFn'));
	}

	function testParse3() {
		$Metadata = new Metadata('mock\db\orm\Stats');

		$pkeys = $Metadata->pkeys();
		$this->assertCount(2, $pkeys);
		$this->assertContains('code', $pkeys);
		$this->assertContains('year', $pkeys);

		$properties = $Metadata->properties();
		$this->assertCount(3, $properties);
		$this->assertArrayHasKey('code', $properties);
		$this->assertTrue($properties['code']['primarykey']);
		$this->assertArrayHasKey('year', $properties);
		$this->assertTrue($properties['year']['primarykey']);
		$this->assertArrayHasKey('score', $properties);
		$this->assertEquals('float', $properties['score']['type']);
	}
}
