<?php
namespace test\db\orm\util;
use renovant\core\db\orm\OrmEvent,
	renovant\core\db\orm\Repository,
	renovant\core\db\orm\util\Metadata,
	renovant\core\db\orm\util\MetadataParser;

class MetadataParserTest extends \PHPUnit\Framework\TestCase {

	function testParse1() {
		$Metadata = new Metadata('test\db\orm\User');

		// data sources
		$this->assertEquals('users', $Metadata->sql('source'));
		$this->assertEquals('users', $Metadata->sql('target'));

		// primary keys
		$pKeys = $Metadata->pKeys();
		$this->assertCount(1, $pKeys);
		$this->assertContains('id', $pKeys);

		// events
		$this->assertEquals('USERS:UPDATING', $Metadata->event(OrmEvent::EVENT_PRE_UPDATE));
		$this->assertTrue($Metadata->event(OrmEvent::EVENT_POST_UPDATE));
		$this->assertFalse($Metadata->event(OrmEvent::EVENT_PRE_FETCH));
		$this->assertFalse($Metadata->event(OrmEvent::EVENT_POST_FETCH));

		// criteria
		$criteria = $Metadata->criteria();
		$this->assertCount(2, $criteria);
		$this->assertArrayHasKey('activeAge', $criteria);
		$this->assertEquals('active,EQ,1|age,GTE,?1', $criteria['activeAge']);
		$this->assertArrayHasKey('dateMonth', $criteria);
		$this->assertEquals('YEAR(lastTime) = ?1 AND MONTH(lastTime) = ?2', $criteria['dateMonth']);

		// fetch order-by
		$orderBy = $Metadata->fetchOrderBy();
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

		// PK criteria
		$this->assertEquals('id,EQ,1', $Metadata->pkCriteria(1));
		$this->assertEquals('id,EQ,847', $Metadata->pkCriteria(847));
		$this->assertEquals('id,EQ,1', $Metadata->pkCriteria(new \test\db\orm\User(['id'=>1])));
		$this->assertEquals('id,EQ,847', $Metadata->pkCriteria(new \test\db\orm\User(['id'=>847])));
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

		$pKeys = $Metadata->pKeys();
		$this->assertCount(2, $pKeys);
		$this->assertContains('code', $pKeys);
		$this->assertContains('year', $pKeys);

		$properties = $Metadata->properties();
		$this->assertCount(3, $properties);
		$this->assertArrayHasKey('code', $properties);
		$this->assertTrue($properties['code']['primarykey']);
		$this->assertArrayHasKey('year', $properties);
		$this->assertTrue($properties['year']['primarykey']);
		$this->assertArrayHasKey('score', $properties);
		$this->assertEquals('float', $properties['score']['type']);

		// PK criteria
		$this->assertEquals('code,EQ,AA|year,EQ,2014', $Metadata->pkCriteria(['AA', 2014]));
		$this->assertEquals('code,EQ,AA|year,EQ,2014', $Metadata->pkCriteria(new \test\db\orm\Stats(['code'=>'AA', 'year'=>2014])));
	}
}
