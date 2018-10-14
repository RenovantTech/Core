<?php
namespace test\db\orm;
use renovant\core\db\orm\Metadata;

class MetadataTest extends \PHPUnit\Framework\TestCase {

	function testParse1() {
		$Metadata = new Metadata('test\db\orm\User');

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

		// fetch subsets
		$this->assertEquals('id, name, score', $Metadata->fetchSubset('mini'));
		$this->assertEquals('id, active, name, age, score', $Metadata->fetchSubset('large'));
		$this->assertEquals('*', $Metadata->fetchSubset('xxx'));

		// validate subsets
		$this->assertEquals(['active', 'name', 'surname'], $Metadata->validateSubset('main'));
		$this->assertEquals(['age', 'score', 'email'], $Metadata->validateSubset('extra'));
		$this->assertEquals(['id', 'active', 'name', 'surname', 'age', 'birthday', 'score', 'email', 'lastTime', 'updatedAt'], $Metadata->validateSubset('xxx'));

		// properties
		$properties = $Metadata->properties();
		$this->assertCount(10, $properties);
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
		$Metadata = new Metadata('test\db\orm\User2');
		// data sources
		$this->assertEquals('users', $Metadata->sql('source'));
		$this->assertEquals('users', $Metadata->sql('target'));
		$this->assertEquals('sp_people_insert, name, surname, age, score, @id', $Metadata->sql('insertFn'));
	}

	function testParse3() {
		$Metadata = new Metadata('test\db\orm\Stats');

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

	function testPkCriteria() {
		$Metadata = new Metadata('test\db\orm\User');
		$this->assertEquals('id,EQ,1', $Metadata->pkCriteria(1));
		$this->assertEquals('id,EQ,847', $Metadata->pkCriteria(847));
		$this->assertEquals('id,EQ,1', $Metadata->pkCriteria(new \test\db\orm\User(['id'=>1])));
		$this->assertEquals('id,EQ,847', $Metadata->pkCriteria(new \test\db\orm\User(['id'=>847])));

		$Metadata = new Metadata('test\db\orm\Stats');
		$this->assertEquals('code,EQ,AA|year,EQ,2014', $Metadata->pkCriteria(['AA', 2014]));
		$this->assertEquals('code,EQ,AA|year,EQ,2014', $Metadata->pkCriteria(new \test\db\orm\Stats(['code'=>'AA', 'year'=>2014])));
	}
}
