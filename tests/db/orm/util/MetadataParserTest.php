<?php
namespace test\db\orm\util;
use renovant\core\db\orm\OrmEvent,
	renovant\core\db\orm\Repository,
	renovant\core\db\orm\util\MetadataParser;

class MetadataParserTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @throws \ReflectionException
	 * @throws \renovant\core\db\orm\Exception
	 */
	function testParse1() {
		$metadata = MetadataParser::parse('test\db\orm\User');

		// data sources
		$this->assertEquals('users', $metadata[Repository::META_SQL]['source']);
		$this->assertEquals('users', $metadata[Repository::META_SQL]['target']);

		// primary keys
		$pkeys = $metadata[Repository::META_PKEYS];
		$this->assertCount(1, $pkeys);
		$this->assertContains('id', $pkeys);

		// events
		$this->assertEquals('USERS:UPDATING', \test\db\orm\User::metadata(Repository::META_EVENTS, OrmEvent::EVENT_PRE_UPDATE));
		$this->assertTrue(\test\db\orm\User::metadata(Repository::META_EVENTS, OrmEvent::EVENT_POST_UPDATE));
		$this->assertFalse(\test\db\orm\User::metadata(Repository::META_EVENTS, OrmEvent::EVENT_PRE_FETCH));
		$this->assertFalse(\test\db\orm\User::metadata(Repository::META_EVENTS, OrmEvent::EVENT_POST_FETCH));

		// criteria
		$criteria = $metadata[Repository::META_CRITERIA];
		$this->assertCount(2, $criteria);
		$this->assertArrayHasKey('activeAge', $criteria);
		$this->assertEquals('active,EQ,1|age,GTE,?1', $criteria['activeAge']);
		$this->assertArrayHasKey('dateMonth', $criteria);
		$this->assertEquals('YEAR(lastTime) = ?1 AND MONTH(lastTime) = ?2', $criteria['dateMonth']);

		// order-by
		$orderBy = $metadata[Repository::META_FETCH_ORDERBY];
		$this->assertCount(1, $orderBy);
		$this->assertArrayHasKey('nameASC', $orderBy);
		$this->assertEquals('name ASC, surname ASC', $orderBy['nameASC']);

		// fetch subsets
		$this->assertEquals('id, name, score', $metadata[Repository::META_FETCH_SUBSETS]['mini']);
		$this->assertEquals('id, active, name, age, score', $metadata[Repository::META_FETCH_SUBSETS]['large']);
		$this->assertEquals(null, $metadata[Repository::META_FETCH_SUBSETS]['xxx']);

		// validate subsets
		$this->assertEquals(['active', 'name', 'surname'], \test\db\orm\User::metadata(Repository::META_VALIDATE_SUBSETS, 'main'));
		$this->assertEquals(['age', 'score', 'email'], \test\db\orm\User::metadata(Repository::META_VALIDATE_SUBSETS, 'extra'));
		$this->assertEquals(['id', 'active', 'name', 'surname', 'age', 'birthday', 'score', 'email', 'lastTime', 'updatedAt'], \test\db\orm\User::metadata(Repository::META_VALIDATE_SUBSETS, 'xxx'));

		// properties
		$properties = $metadata[Repository::META_PROPS];
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

	/**
	 * @throws \ReflectionException
	 * @throws \renovant\core\db\orm\Exception
	 */
	function testParse2() {
		$metadata = MetadataParser::parse('test\db\orm\User2');
		// data sources
		$this->assertEquals('users', $metadata[Repository::META_SQL]['source']);
		$this->assertEquals('users', $metadata[Repository::META_SQL]['target']);
		$this->assertEquals('sp_people_insert, name, surname, age, score, @id', $metadata[Repository::META_SQL]['insertFn']);
	}

	/**
	 * @throws \ReflectionException
	 * @throws \renovant\core\db\orm\Exception
	 */
	function testParse3() {
		$metadata = MetadataParser::parse('test\db\orm\Stats');

		$pkeys = $metadata[Repository::META_PKEYS];
		$this->assertCount(2, $pkeys);
		$this->assertContains('code', $pkeys);
		$this->assertContains('year', $pkeys);

		$properties = $metadata[Repository::META_PROPS];
		$this->assertCount(3, $properties);
		$this->assertArrayHasKey('code', $properties);
		$this->assertTrue($properties['code']['primarykey']);
		$this->assertArrayHasKey('year', $properties);
		$this->assertTrue($properties['year']['primarykey']);
		$this->assertArrayHasKey('score', $properties);
		$this->assertEquals('float', $properties['score']['type']);
	}

	/**
	 * @throws \ReflectionException
	 * @throws \renovant\core\db\orm\Exception
	 */
	function testPkCriteria() {
		$this->assertEquals('id,EQ,1', \test\db\orm\User::metadata(Repository::META_PKCRITERIA, 1));
		$this->assertEquals('id,EQ,847', \test\db\orm\User::metadata(Repository::META_PKCRITERIA, 847));
		$this->assertEquals('id,EQ,1', \test\db\orm\User::metadata(Repository::META_PKCRITERIA, new \test\db\orm\User(['id'=>1])));
		$this->assertEquals('id,EQ,847', \test\db\orm\User::metadata(Repository::META_PKCRITERIA, new \test\db\orm\User(['id'=>847])));

		$this->assertEquals('code,EQ,AA|year,EQ,2014', \test\db\orm\Stats::metadata(Repository::META_PKCRITERIA, ['AA', 2014]));
		$this->assertEquals('code,EQ,AA|year,EQ,2014', \test\db\orm\Stats::metadata(Repository::META_PKCRITERIA, new \test\db\orm\Stats(['code'=>'AA', 'year'=>2014])));
	}
}
