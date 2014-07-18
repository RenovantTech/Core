<?php
namespace test\db\orm;
use metadigit\core\Kernel,
	metadigit\core\context\Context,
	metadigit\core\db\orm\Repository,
	metadigit\core\util\DateTime;

class Repository2Test extends \PHPUnit_Framework_TestCase {

	static function setUpBeforeClass() {
		Kernel::pdo('mysql')->exec('
			DROP TABLE IF EXISTS `stats`;
		');
		Kernel::pdo('mysql')->exec('
			CREATE TABLE IF NOT EXISTS `stats` (
				code		varchar(5) not NULL,
				year		year not NULL,
				score		decimal(4,2) unsigned,
				PRIMARY KEY(code, year)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		');
	}

	static function tearDownAfterClass() {
		Kernel::pdo('mysql')->exec('
			DROP TABLE IF EXISTS `stats`;
		');
	}

	protected function setUp() {
		Kernel::pdo('mysql')->exec('
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
		$Context = Context::factory('mock.db.orm');
		$StatsRepository = new Repository('mock\db\orm\Stats', 'mysql');
		$StatsRepository->setContext($Context);
		$this->assertInstanceOf('metadigit\core\db\orm\Repository', $StatsRepository);
		return $StatsRepository;
	}

	/**
	 * @depends testConstructor
	 */
	function testCreate(Repository $StatsRepository) {
		$Stats = $StatsRepository->create(['code'=>'EE', 'year'=>2013, 'score'=>3.5]);
		$this->assertInstanceOf('mock\db\orm\Stats', $Stats);
		$this->assertEquals('EE', $Stats->code);
		$this->assertEquals(2013, $Stats->year);
		$this->assertEquals(3.5, $Stats->score);
	}

	/**
	 * @depends testConstructor
	 */
	function testDelete(Repository $StatsRepository) {
		// passing Entity
		$Stats = $StatsRepository->fetch(['AA',2013]);
		$this->assertTrue($StatsRepository->delete($Stats));
		$this->assertFalse($StatsRepository->fetch(['AA',2013]));

		// passing key
		$this->assertTrue($StatsRepository->delete(['BB',2013]));
		$this->assertFalse($StatsRepository->fetch(['BB',2013]));
	}

	/**
	 * @depends testConstructor
	 */
	function testDeleteAll(Repository $StatsRepository) {
		$this->assertSame(4, $StatsRepository->deleteAll(null, null, 'year,EQ,2013'));
		$this->assertFalse($StatsRepository->fetch(['AA',2013]));
		$this->assertFalse($StatsRepository->fetch(['BB',2013]));

		$this->assertSame(3, $StatsRepository->deleteAll(3, 'score.DESC', 'year,GT,2013'));
		$this->assertFalse($StatsRepository->fetch(['BB',2014]));
		$this->assertFalse($StatsRepository->fetch(['CC',2014]));
		$this->assertFalse($StatsRepository->fetch(['DD',2014]));
		$this->assertInstanceOf('mock\db\orm\Stats', $StatsRepository->fetch(['AA',2014]));
	}
	/**
	 * @depends testConstructor
	 */
	function testFetch(Repository $StatsRepository) {
		// FETCH_OBJ
		$Stats = $StatsRepository->fetch(['AA',2013]);
		$this->assertInstanceOf('mock\db\orm\Stats', $Stats);
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
	 */
	function testFetchOne(Repository $StatsRepository) {
		// FETCH_OBJ
		$Stats = $StatsRepository->fetchOne(2, 'score.ASC', 'year,EQ,2014');
		$this->assertInstanceOf('mock\db\orm\Stats', $Stats);
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
	 */
	function testFetchAll(Repository $StatsRepository) {
		// FETCH_OBJ
		$stats = $StatsRepository->fetchAll(1, 3, 'code.DESC', 'score,LTE,8');
		$this->assertCount(3, $stats);
		$this->assertInstanceOf('mock\db\orm\Stats', $stats[0]);
		$this->assertSame('DD', $stats[0]->code);
		$this->assertSame(CC, $stats[1]->code);

		// FETCH_ARRAY
		$stats = $StatsRepository->fetchAll(1, 3, 'code.ASC', 'score,GTE,5', Repository::FETCH_ARRAY);
		$this->assertCount(3, $stats);
		$this->assertTrue(is_array($stats[0]));
		$this->assertSame('AA', $stats[0]['code']);
		$this->assertSame('AA', $stats[1]['code']);
	}

	/**
	 * @depends testConstructor
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
	 */
	function testInsert(Repository $StatsRepository) {
		// INSERT full object
		$Stats = new \mock\db\orm\Stats(['code'=>'EE', 'year'=>2015, 'score'=>9.5]);
		$this->assertInstanceOf('mock\db\orm\Stats', $StatsRepository->insert($Stats));
		$Stats = $StatsRepository->fetch(['EE', 2015]);
		$this->assertInstanceOf('mock\db\orm\Stats', $Stats);
		$this->assertSame('EE', $Stats->code);
		$this->assertSame(2015, $Stats->year);
		$this->assertSame(9.5, $Stats->score);

		// INSERT empty object passing values
		$Stats = new \mock\db\orm\Stats;
		$this->assertInstanceOf('mock\db\orm\Stats', $StatsRepository->insert($Stats, [ 'code'=>'FF', 'year'=>2015, 'score'=>8.4 ]));
		$Stats = $StatsRepository->fetch(['FF', 2015]);
		$this->assertInstanceOf('mock\db\orm\Stats', $Stats);
		$this->assertSame('FF', $Stats->code);
		$this->assertSame(2015, $Stats->year);
		$this->assertSame(8.4, $Stats->score);


		// INSERT null key & values
		$this->assertInstanceOf('mock\db\orm\Stats', $StatsRepository->insert(null, [ 'code'=>'GG', 'year'=>2015, 'score'=>null ]));
		$Stats = $StatsRepository->fetch(['GG', 2015]);
		$this->assertInstanceOf('mock\db\orm\Stats', $Stats);
		$this->assertSame('GG', $Stats->code);
		$this->assertSame(2015, $Stats->year);
		$this->assertSame(0.0, $Stats->score);

		// INSERT key & values
		$this->assertInstanceOf('mock\db\orm\Stats', $StatsRepository->insert(['HH', 2015], [ 'score'=>null ]));
		$Stats = $StatsRepository->fetch(['HH', 2015]);
		$this->assertInstanceOf('mock\db\orm\Stats', $Stats);
		$this->assertSame('HH', $Stats->code);
		$this->assertSame(2015, $Stats->year);
		$this->assertSame(0.0, $Stats->score);
	}

	/**
	 * @depends testConstructor
	 */
	function __testUpdate(Repository $StatsRepository) {
		// 1 - change Entity directly
		$Stats = $StatsRepository->fetch(['AA', 2013]);
		$Stats->score = 12;
		$this->assertInstanceOf('mock\db\orm\Stats', $StatsRepository->update($Stats));
		$Stats = $StatsRepository->fetch(['AA', 2013]);
		$this->assertSame(12.0, $Stats->score);
		// 2 - pass new values array
		$this->assertInstanceOf('mock\db\orm\Stats', $StatsRepository->update(['BB',2013], ['score'=>11]));
		$Stats = $StatsRepository->fetch(['BB', 2013]);
		$this->assertSame(11.0, $Stats->score);
		// 2bis - pass new values array
		$Stats = $StatsRepository->fetch(['CC', 2013]);
		$this->assertInstanceOf('mock\db\orm\Stats', $StatsRepository->update($Stats, ['score'=>13]));
		$this->assertSame(13, $Stats->score);
		$Stats = $StatsRepository->fetch(['CC', 2013]);
		$this->assertSame(13.0, $Stats->score);
		// 1+2 - change Entity & pass new values
		$Stats = $StatsRepository->fetch(['DD', 2013]);
		$Stats->score = 15;
		$this->assertInstanceOf('mock\db\orm\Stats', $StatsRepository->update($Stats, ['score'=>14]));
		$this->assertSame(14, $Stats->score);
		$Stats = $StatsRepository->fetch(['DD', 2013]);
		$this->assertSame(14.0, $Stats->score);
		// test without re-fetch
		$Stats = $StatsRepository->fetch(['AA', 2014]);
		$this->assertInstanceOf('mock\db\orm\Stats', $StatsRepository->update($Stats, ['score'=>4.2]), ['fetch'=>false]);
		$this->assertSame(4.2, $Stats->score);
	}
}