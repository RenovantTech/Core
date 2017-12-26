<?php
namespace test\db\orm;
use metadigit\core\sys,
	metadigit\core\context\Context,
	metadigit\core\db\orm\Repository;

class Repository2Test extends \PHPUnit\Framework\TestCase {

	static function setUpBeforeClass() {
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS `stats`;
		');
		sys::pdo('mysql')->exec('
			CREATE TABLE IF NOT EXISTS `stats` (
				code		varchar(5) not NULL,
				year		year not NULL,
				score		decimal(4,2) unsigned,
				PRIMARY KEY(code, year)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		');
	}

	static function tearDownAfterClass() {
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS `stats`;
		');
	}

	protected function setUp() {
		sys::pdo('mysql')->exec('
			TRUNCATE TABLE `stats`;
			INSERT INTO `stats` (code, year, score) VALUES ("AA", 2013, 6.5);
			INSERT INTO `stats` (code, year, score) VALUES ("BB", 2013, 8.3);
			INSERT INTO `stats` (code, year, score) VALUES ("CC", 2013, 6.5);
			INSERT INTO `stats` (code, year, score) VALUES ("DD", 2013, 6.5);
			INSERT INTO `stats` (code, year, score) VALUES ("AA", 2014, 6.5);
			INSERT INTO `stats` (code, year, score) VALUES ("BB", 2014, 7.5);
			INSERT INTO `stats` (code, year, score) VALUES ("CC", 2014, 8.5);
			INSERT INTO `stats` (code, year, score) VALUES ("DD", 2014, 9.5);
		');
	}

	function testConstructor() {
		$StatsRepository = sys::context()->container()->get('test.db.orm.StatsRepository');
		$this->assertInstanceOf('metadigit\core\db\orm\Repository', $StatsRepository);
		return $StatsRepository;
	}

	/**
	 * @depends testConstructor
	 * @param Repository $StatsRepository
	 */
	function testCreate(Repository $StatsRepository) {
		$Stats = $StatsRepository->create(['code'=>'EE', 'year'=>2013, 'score'=>3.5]);
		$this->assertInstanceOf('test\db\orm\Stats', $Stats);
		$this->assertEquals('EE', $Stats->code);
		$this->assertEquals(2013, $Stats->year);
		$this->assertEquals(3.5, $Stats->score);
	}

	/**
	 * @depends testConstructor
	 * @param Repository $StatsRepository
	 */
	function testDelete(Repository $StatsRepository) {
		// passing Entity
		$Stats = $StatsRepository->fetch(['AA',2013]);
		$this->assertInstanceOf('test\db\orm\Stats', $StatsRepository->delete($Stats));
		$this->assertFalse($StatsRepository->fetch(['AA',2013]));

		// passing key
		$this->assertInstanceOf('test\db\orm\Stats', $StatsRepository->delete(['BB',2013]));
		$this->assertFalse($StatsRepository->fetch(['BB',2013]));
	}

	/**
	 * @depends testConstructor
	 * @param Repository $StatsRepository
	 */
	function testDeleteAll(Repository $StatsRepository) {
		$this->assertSame(4, $StatsRepository->deleteAll(null, null, 'year,EQ,2013'));
		$this->assertFalse($StatsRepository->fetch(['AA',2013]));
		$this->assertFalse($StatsRepository->fetch(['BB',2013]));

		$this->assertSame(3, $StatsRepository->deleteAll(3, 'score.DESC', 'year,GT,2013'));
		$this->assertFalse($StatsRepository->fetch(['BB',2014]));
		$this->assertFalse($StatsRepository->fetch(['CC',2014]));
		$this->assertFalse($StatsRepository->fetch(['DD',2014]));
		$this->assertInstanceOf('test\db\orm\Stats', $StatsRepository->fetch(['AA',2014]));
	}

	/**
	 * @depends testConstructor
	 * @param Repository $StatsRepository
	 */
	function testFetch(Repository $StatsRepository) {
		// FETCH_OBJ
		$Stats = $StatsRepository->fetch(['AA',2013]);
		$this->assertInstanceOf('test\db\orm\Stats', $Stats);
		$this->assertSame('AA', $Stats->code);
		$this->assertSame(2013, $Stats->year);
		$this->assertSame(6.5, $Stats->score);

		// FETCH_ARRAY
		$statsData = $StatsRepository->fetch(['AA',2013], Repository::FETCH_ARRAY);
		$this->assertTrue(is_array($statsData));
		$this->assertCount(3, $statsData);
		$this->assertSame('AA', $statsData['code']);
		$this->assertSame(2013, $statsData['year']);
		$this->assertSame(6.5, $statsData['score']);
	}

	/**
	 * @depends testConstructor
	 * @param Repository $StatsRepository
	 */
	function testFetchOne(Repository $StatsRepository) {
		// FETCH_OBJ
		$Stats = $StatsRepository->fetchOne(2, 'score.ASC', 'year,EQ,2014');
		$this->assertInstanceOf('test\db\orm\Stats', $Stats);
		$this->assertSame('BB', $Stats->code);
		$this->assertSame(2014, $Stats->year);

		// FETCH_ARRAY
		$statsData = $StatsRepository->fetchOne(3, 'score.ASC', 'year,EQ,2014', Repository::FETCH_ARRAY);
		$this->assertTrue(is_array($statsData));
		$this->assertCount(3, $statsData);
		$this->assertSame('CC', $statsData['code']);
		$this->assertSame(2014, $statsData['year']);
	}

	/**
	 * @depends testConstructor
	 * @param Repository $StatsRepository
	 */
	function testFetchAll(Repository $StatsRepository) {
		// FETCH_OBJ
		$stats = $StatsRepository->fetchAll(1, 3, 'code.DESC', 'score,LTE,8');
		$this->assertCount(3, $stats);
		$this->assertInstanceOf('test\db\orm\Stats', $stats[0]);
		$this->assertSame('DD', $stats[0]->code);
		$this->assertSame('CC', $stats[1]->code);

		// FETCH_ARRAY
		$stats = $StatsRepository->fetchAll(1, 3, 'code.ASC', 'score,GTE,5', Repository::FETCH_ARRAY);
		$this->assertCount(3, $stats);
		$this->assertTrue(is_array($stats[0]));
		$this->assertSame('AA', $stats[0]['code']);
		$this->assertSame('AA', $stats[1]['code']);
	}

	/**
	 * @depends testConstructor
	 * @param Repository $StatsRepository
	 */
	function testToArray(Repository $StatsRepository) {
		// no subset
		$Stats = $StatsRepository->fetch(['AA', 2013]);
		$data = $StatsRepository->toArray($Stats);
		$this->assertCount(3, $data);
		$this->assertSame('AA', $data['code']);
		$this->assertSame(2013, $data['year']);
		$this->assertSame(6.5, $data['score']);

		// array of entities
		$stats = $StatsRepository->fetchAll(1, 20, 'score.DESC', 'year,EQ,2014');
		$data = $StatsRepository->toArray($stats);
		$this->assertCount(4, $data);
		$this->assertSame('DD', $data[0]['code']);
		$this->assertSame('CC', $data[1]['code']);
	}

	/**
	 * @depends testConstructor
	 * @param Repository $StatsRepository
	 */
	function testInsert(Repository $StatsRepository) {
		// INSERT null key & object
		$Stats = new \test\db\orm\Stats(['code'=>'EE', 'year'=>2015, 'score'=>9.5]);
		$this->assertInstanceOf('test\db\orm\Stats', $StatsRepository->insert(null, $Stats));
		$Stats = $StatsRepository->fetch(['EE', 2015]);
		$this->assertInstanceOf('test\db\orm\Stats', $Stats);
		$this->assertSame('EE', $Stats->code);
		$this->assertSame(2015, $Stats->year);
		$this->assertSame(9.5, $Stats->score);

		// INSERT null key & values
		$Stats = new \test\db\orm\Stats;
		$this->assertInstanceOf('test\db\orm\Stats', $StatsRepository->insert(null, [ 'code'=>'FF', 'year'=>2015, 'score'=>8.4 ]));
		$Stats = $StatsRepository->fetch(['FF', 2015]);
		$this->assertInstanceOf('test\db\orm\Stats', $Stats);
		$this->assertSame('FF', $Stats->code);
		$this->assertSame(2015, $Stats->year);
		$this->assertSame(8.4, $Stats->score);

		// INSERT key & values
		$this->assertInstanceOf('test\db\orm\Stats', $StatsRepository->insert(['HH', 2015], [ 'score'=>null ]));
		$Stats = $StatsRepository->fetch(['HH', 2015]);
		$this->assertInstanceOf('test\db\orm\Stats', $Stats);
		$this->assertSame('HH', $Stats->code);
		$this->assertSame(2015, $Stats->year);
		$this->assertSame(0.0, $Stats->score);
	}

	/**
	 * @depends testConstructor
	 * @param Repository $StatsRepository
	 */
	function testUpdate(Repository $StatsRepository) {

		// 1 - change Entity directly
		$Stats = $StatsRepository->fetch(['AA', 2013]);
		$Stats->score = 12;
		$this->assertInstanceOf('test\db\orm\Stats', $StatsRepository->update(['AA', 2013], $Stats));
		$Stats = $StatsRepository->fetch(['AA', 2013]);
		$this->assertSame(12.0, $Stats->score);

		// 2 - pass new values array
		$this->assertInstanceOf('test\db\orm\Stats', $StatsRepository->update(['BB',2013], ['score'=>11]));
		$Stats = $StatsRepository->fetch(['BB', 2013]);
		$this->assertSame(11.0, $Stats->score);

		// test without re-fetch
		$Stats = $StatsRepository->fetch(['AA', 2014]);
		$this->assertTrue($StatsRepository->update(['AA', 2014], ['score'=>4.2], true, false));
	}
}
