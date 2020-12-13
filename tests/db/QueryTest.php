<?php
namespace test\db;
use renovant\core\sys,
	renovant\core\db\Query;

class QueryTest extends \PHPUnit\Framework\TestCase {

	static function setUpBeforeClass():void {
		sys::pdo('mysql')->exec('
			CREATE TABLE IF NOT EXISTS `people` (
				id			SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
				name		VARCHAR(20),
				surname		VARCHAR(20),
				age			INTEGER UNSIGNED NOT NULL DEFAULT 0,
				score		DECIMAL(4,2) UNSIGNED NOT NULL DEFAULT 0,
				PRIMARY KEY(id)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
			
			CREATE PROCEDURE sp_people (
				IN p_name		VARCHAR(20),
				IN p_surname	VARCHAR(20),
				IN p_age		INTEGER UNSIGNED,
				IN p_score		DECIMAL(4,2),
				OUT p_id		INTEGER UNSIGNED,
				OUT p_time		DATETIME
			)
			BEGIN
				DECLARE LID integer;
				INSERT INTO people (name, surname, age, score) VALUES (p_name, p_surname, p_age, p_score);
				SET p_id = LAST_INSERT_ID();
				SET p_time = NOW();
			END;
			
			CREATE TABLE IF NOT EXISTS `sales` (
				year		YEAR NOT NULL,
				product_id	INTEGER UNSIGNED NOT NULL,
				sales1		DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
				sales2		DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
				sales3		DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
				sales4		DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
				sales5		DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
				sales6		DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
				sales7		DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
				sales8		DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
				sales9		DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
				sales10		DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
				sales11		DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
				sales12		DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
				target1		DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
				target2		DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
				target3		DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
				target4		DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
				target5		DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
				target6		DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
				target7		DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
				target8		DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
				target9		DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
				target10	DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
				target11	DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
				target12	DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0,
				PRIMARY KEY(year, product_id)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		');
	}

	static function tearDownAfterClass():void {
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS `people`;
			DROP PROCEDURE IF EXISTS sp_people;
			DROP TABLE IF EXISTS `sales`;
		');
	}

	protected function setUp():void {
		sys::pdo('mysql')->exec('
			TRUNCATE `people`;
			INSERT INTO `people` (id, name, surname, age, score) VALUES (1, "Albert",	"Brown", 21, 32.5);
			INSERT INTO `people` (id, name, surname, age, score) VALUES (2, "Barbara",	"Yellow",25, 8.6);
			INSERT INTO `people` (id, name, surname, age, score) VALUES (3, "Carl",		"Green", 21, 15.4);
			INSERT INTO `people` (id, name, surname, age, score) VALUES (4, "Don",		"Green", 17, 25);
			INSERT INTO `people` (id, name, surname, age, score) VALUES (5, "Emily",	"Red",   18, 28);
			INSERT INTO `people` (id, name, surname, age, score) VALUES (6, "Franz",	"Green", 28, 19.5);
			INSERT INTO `people` (id, name, surname, age, score) VALUES (7, "Gen",		"Green", 25, 12);
			INSERT INTO `people` (id, name, surname, age, score) VALUES (8, "Hugh",		"Red",   23, 23.4);
			TRUNCATE `sales`;
		');
	}

	function testExecCount() {
		// criteria() values
		$count = (new Query('mysql'))->on('people')->criteria('name LIKE "%ra%"')->execCount();
		$this->assertEquals(2, $count);
		$count = (new Query('mysql'))->on('people')->criteriaExp('name,LIKE,%ra%')->execCount();
		$this->assertEquals(2, $count);

		// criteria() params + execCount() values
		$count = (new Query('mysql'))->on('people')->criteria('name LIKE :name')->params(['name'=>'%ra%'])->execCount();
		$this->assertEquals(2, $count);
		$count = (new Query('mysql'))->on('people')->criteriaExp('name,LIKE,:name')->params(['name'=>'%ra%'])->execCount();
		$this->assertEquals(2, $count);

		// criteria() double params + execCount() values
		$count = (new Query('mysql'))->on('people')->criteria('age >= :age AND score > :age')->params(['age'=>21])->execCount();
		$this->assertEquals(2, $count);
		$count = (new Query('mysql'))->on('people')->criteriaExp('age,GTE,:age|score,GT,:age')->params(['age'=>21])->execCount();
		$this->assertEquals(2, $count);

		// criteria() params & values + execCount() values
		$Query = (new Query('mysql'))->on('people')->criteria('age >= 20 AND score > :score');
		$this->assertEquals(2, $Query->params(['score'=>20])->execCount());
		$this->assertEquals(1, $Query->params(['score'=>30])->execCount());
		$Query = (new Query('mysql'))->on('people')->criteriaExp('age,GTE,20|score,GT,:score');
		$this->assertEquals(2, $Query->params(['score'=>20])->execCount());
		$this->assertEquals(1, $Query->params(['score'=>30])->execCount());

		// GROUP BY
		$Query = (new Query('mysql'))->on('people')->groupBy('age')->orderBy('id ASC');
		$this->assertEquals(2, $Query->execCount()); // 2 people of age 21

		// GROUP BY HAVING
		$Query = (new Query('mysql'))->on('people')->groupBy('age')->having('age > 23');
		$this->assertEquals(2, $Query->execCount()); // 2 people of age 25

		// GROUP BY WITH ROLLUP
		$Query = (new Query('mysql'))->on('people')->groupBy('age')->withRollup();
		$this->assertEquals(1, $Query->execCount());
	}

	function testExecDelete() {
		// criteria() values
		$count = (new Query('mysql'))->on('people')->criteria('surname = "Brown"')->execDelete();
		$this->assertEquals(1, $count);
		$count = (new Query('mysql'))->on('people')->criteriaExp('surname,EQ,Yellow')->execDelete();
		$this->assertEquals(1, $count);

		// criteria() params + execDelete() values
		$count = (new Query('mysql'))->on('people')->criteria('name = :name')->params(['name'=>'Carl'])->execDelete();
		$this->assertEquals(1, $count);
		$count = (new Query('mysql'))->on('people')->criteriaExp('name,EQ,:name')->params(['name'=>'Don'])->execDelete();
		$this->assertEquals(1, $count);

		// criteria() double params + execDelete() values
		$count = (new Query('mysql'))->on('people')->criteria('age = :age AND score < :age')->params(['age'=>18])->execDelete();
		$this->assertEquals(0, $count);
		$count = (new Query('mysql'))->on('people')->criteriaExp('age,EQ,:age|score,LT,:age')->params(['age'=>18])->execDelete();
		$this->assertEquals(0, $count);

		// criteria() params & values + execDelete() values
		$Query = (new Query('mysql'))->on('people')->criteria('age >= 18 AND score > :score');
		$this->assertEquals(1, $Query->params(['score'=>27])->execDelete());
		$this->assertEquals(1, $Query->params(['score'=>23])->execDelete());
		$Query = (new Query('mysql'))->on('people')->criteriaExp('age,GTE,18|score,GT,:score');
		$this->assertEquals(1, $Query->params(['score'=>19])->execDelete());
		$this->assertEquals(1, $Query->params(['score'=>11])->execDelete());
	}

	function testExecInsert() {
		$Query = (new Query('mysql'))->on('people');
		$this->assertEquals(1, $Query->execInsert(['name'=>'John', 'surname'=>'Foo']));
		$this->assertEquals(1, (new Query('mysql'))->on('people')->criteria('surname = "Foo"')->execCount());
		$this->assertEquals(1, $Query->execInsert(['name'=>'Dick', 'surname'=>'Foo']));
		$this->assertEquals(2, (new Query('mysql'))->on('people')->criteria('surname = "Foo"')->execCount());

		$Query = (new Query('mysql'))->on('sales');
		$this->assertEquals(1, $Query->execInsert(['year'=>2014, 'product_id'=>1, 'sales1'=>25500, 'sales2'=>0, 'sales3'=>32000, 'sales4'=>28450.50, 'sales5'=>0, 'sales6'=>0, 'sales7'=>0, 'sales8'=>0, 'sales9'=>0, 'sales10'=>0, 'sales11'=>0, 'sales12'=>0]));
		$this->assertEquals(1, (new Query('mysql'))->on('sales')->criteria('year = 2014 AND product_id = 1')->execCount());
	}

	function testExecInsertException() {
		try {
			$Query = (new Query('mysql'))->on('sales');
			$Query->execInsert(['year'=>2014, 'product_id'=>1, 'sales1'=>null, 'sales2'=>0, 'sales3'=>32000, 'sales4'=>28450.50, 'sales5'=>0, 'sales6'=>0, 'sales7'=>0, 'sales8'=>0, 'sales9'=>0, 'sales10'=>0, 'sales11'=>0, 'sales12'=>0]);
			$this->fail('Expected PDOException not thrown');
		} catch(\PDOException $Ex) {
			$this->assertEquals(23000, $Ex->getCode());
			$this->assertMatchesRegularExpression('/cannot be null/', $Ex->getMessage());
		}
	}

	function testExecInsertUpdate() {
		$Query = (new Query('mysql'))->on('people');
		$this->assertEquals(2, $Query->execInsertUpdate(['id'=>1, 'name'=>'Albert', 'surname'=>'Brown', 'age'=>22],['id'])); // row count ON DUPLICATE is 2 !!!
		$this->assertEquals(1, (new Query('mysql'))->on('people')->criteria('id = 1 AND age = 22')->execCount());
		$Query = (new Query('mysql'))->on('people');
		$this->assertEquals(1, $Query->execInsertUpdate(['id'=>9, 'name'=>'Dick', 'surname'=>'Foo'],['id']));
		$this->assertEquals(1, (new Query('mysql'))->on('people')->criteria('surname = "Foo"')->execCount());
	}

	function testExecSelect() {
		// criteria() values
		$items = (new Query('mysql'))->on('people')->criteria('surname = "Green"')->execSelect()->fetchAll(\PDO::FETCH_ASSOC);
		$this->assertCount(4, $items);
		$items = (new Query('mysql'))->on('people')->criteriaExp('surname,EQ,Green')->execSelect()->fetchAll(\PDO::FETCH_ASSOC);
		$this->assertCount(4, $items);

		// criteria() params + execSelect() values
		$items = (new Query('mysql'))->on('people')->criteria('name LIKE :name')->params(['name'=>'%ra%'])->execSelect()->fetchAll(\PDO::FETCH_ASSOC);
		$this->assertCount(2, $items);
		$items = (new Query('mysql'))->on('people')->criteriaExp('name,LIKE,:name')->params(['name'=>'%ra%'])->execSelect()->fetchAll(\PDO::FETCH_ASSOC);
		$this->assertCount(2, $items);

		// criteria() double params + execSelect() values
		$items = (new Query('mysql'))->on('people')->criteria('age >= :age AND score > :age')->params(['age'=>21])->execSelect()->fetchAll(\PDO::FETCH_ASSOC);
		$this->assertCount(2, $items);
		$items = (new Query('mysql'))->on('people')->criteriaExp('age,GTE,:age|score,GT,:age')->params(['age'=>21])->execSelect()->fetchAll(\PDO::FETCH_ASSOC);
		$this->assertCount(2, $items);

		// criteria() params & values + execSelect() values
		$Query = (new Query('mysql'))->on('people')->criteria('age >= 20 AND score > :score');
		$this->assertCount(2, $Query->params(['score'=>20])->execSelect()->fetchAll(\PDO::FETCH_ASSOC));
		$this->assertCount(1, $Query->params(['score'=>30])->execSelect()->fetchAll(\PDO::FETCH_ASSOC));
		$Query = (new Query('mysql'))->on('people')->criteriaExp('age,GTE,20|score,GT,:score');
		$this->assertCount(2, $Query->params(['score'=>20])->execSelect()->fetchAll(\PDO::FETCH_ASSOC));
		$this->assertCount(1, $Query->params(['score'=>30])->execSelect()->fetchAll(\PDO::FETCH_ASSOC));

		// LIMIT & OFFSET, PAGE & PAGE SIZE
		$Query = (new Query('mysql'))->on('people')->criteriaExp('surname,EQ,Green');
		$data = $Query->limit(2)->execSelect()->fetchAll(\PDO::FETCH_ASSOC);
		$this->assertCount(2, $data);
		$this->assertEquals(3, $data[0]['id']);
		$data = $Query->limit(2)->offset(1)->execSelect()->fetchAll(\PDO::FETCH_ASSOC);
		$this->assertCount(2, $data);
		$this->assertEquals(4, $data[0]['id']);
		$data = $Query->page(2, 2)->execSelect()->fetchAll(\PDO::FETCH_ASSOC);
		$this->assertCount(2, $data);
		$this->assertEquals(6, $data[0]['id']);
		$data = $Query->page(2, 3)->execSelect()->fetchAll(\PDO::FETCH_ASSOC);
		$this->assertCount(1, $data);
		$this->assertEquals(7, $data[0]['id']);

		// GROUP BY
		$data = (new Query('mysql'))->on('people')->groupBy('age')->execSelect('age, COUNT(*) AS n')->fetchAll(\PDO::FETCH_ASSOC);
		$this->assertCount(6, $data);
		$this->assertEquals(['age'=>21, 'n'=>2], $data[2]);

		// GROUP BY HAVING
		$data = (new Query('mysql'))->on('people')->groupBy('age')->having('age > 23')->execSelect('age, COUNT(*) AS n')->fetchAll(\PDO::FETCH_ASSOC);
		$this->assertCount(2, $data);
		$this->assertEquals(['age'=>25, 'n'=>2], $data[0]);

		// GROUP BY WITH ROLLUP
		$data = (new Query('mysql'))->on('people')->groupBy('age')->withRollup()->execSelect('age, COUNT(*) AS n')->fetchAll(\PDO::FETCH_ASSOC);
		$this->assertCount(7, $data);
		$this->assertEquals(['age'=>'', 'n'=>8], $data[6]);
	}

	function testExecUpdate() {
		// criteria() values
		$count = (new Query('mysql'))->on('people')->criteria('age = 25')->execUpdate(['name'=>'Zack', 'surname'=>'Black']);
		$this->assertEquals(2, $count);
		$this->assertEquals(2, (new Query('mysql'))->on('people')->criteria('name = "Zack" AND surname = "Black" AND age = 25')->execCount());

		// criteria() params + execSelect() values
		$count = (new Query('mysql'))->on('people')->criteria('age = :age')->execUpdate(['age'=>25, 'name'=>'Dick', 'surname'=>'Grey']);
		$this->assertEquals(2, $count);
		$this->assertEquals(2, (new Query('mysql'))->on('people')->criteria('name = "Dick" AND surname = "Grey" AND age = 25')->execCount());

		// criteria() double params + execSelect() values
		$count = (new Query('mysql'))->on('people')->criteria('age >= :age AND score >= :age')->params(['age'=>21])->execUpdate(['name'=>'Tod', 'surname'=>'DarkGrey']);
		$this->assertEquals(2, $count);
		$this->assertEquals(2, (new Query('mysql'))->on('people')->criteria('age >= 21 AND score >= 21')->execCount());

		// criteria() params & values + execSelect() values
		$Query = (new Query('mysql'))->on('people')->criteria('age >= 21 AND score >= :score');
		$this->assertEquals(1, $Query->params(['score'=>30])->execUpdate(['name'=>'Xiao', 'surname'=>'Ming']));
		$this->assertEquals(1, (new Query('mysql'))->on('people')->criteria('surname = "Ming" AND age >= 21 AND score >= 30')->execCount());
		$this->assertEquals(2, $Query->params(['score'=>20])->execUpdate(['name'=>'Xiao', 'surname'=>'Ping']));
		$this->assertEquals(2, (new Query('mysql'))->on('people')->criteria('surname = "Ping" AND age >= 21 AND score >= 20')->execCount());
	}

	function testExecCall() {
		$Query = (new Query('mysql'))->on('sp_people');
		$data = $Query->execCall(['name'=>'Xiao', 'surname'=>'Ming', 'age'=>25, 'score'=>30, '@id', '@time']);
		$this->assertEquals(9, $data['id']);
	}

	function testCriteriaExp() {
		// EQ
		$this->assertEquals(2, (new Query('mysql'))->on('people')->criteriaExp('surname,EQ,Red')->execCount());
		$this->assertEquals(2, (new Query('mysql'))->on('people')->criteriaExp('surname,EQ,:name')->params(['name'=>'Red'])->execCount());
		// !EQ
		$this->assertEquals(6, (new Query('mysql'))->on('people')->criteriaExp('surname,!EQ,Red')->execCount());
		$this->assertEquals(6, (new Query('mysql'))->on('people')->criteriaExp('surname,!EQ,:name')->params(['name'=>'Red'])->execCount());

		// NULL
		$this->assertEquals(0, (new Query('mysql'))->on('people')->criteriaExp('score,NULL')->execCount());
		// !NULL
		$this->assertEquals(8, (new Query('mysql'))->on('people')->criteriaExp('score,!NULL')->execCount());

		// LIKE, LIKEHAS, LIKESTART, LIKEEND
		$this->assertEquals(1, (new Query('mysql'))->on('people')->criteriaExp('name,LIKE,Don')->execCount());
		$this->assertEquals(2, (new Query('mysql'))->on('people')->criteriaExp('name,LIKEHAS,ra')->execCount());
		$this->assertEquals(4, (new Query('mysql'))->on('people')->criteriaExp('surname,LIKESTART,gr')->execCount());
		$this->assertEquals(2, (new Query('mysql'))->on('people')->criteriaExp('surname,LIKEEND,ed')->execCount());
		// !LIKE, !LIKEHAS, !LIKESTART, !LIKEEND
		$this->assertEquals(7, (new Query('mysql'))->on('people')->criteriaExp('surname,!LIKE,Brown')->execCount());
		$this->assertEquals(6, (new Query('mysql'))->on('people')->criteriaExp('name,!LIKEHAS,ra')->execCount());
		$this->assertEquals(6, (new Query('mysql'))->on('people')->criteriaExp('surname,!LIKESTART,re')->execCount());
		$this->assertEquals(7, (new Query('mysql'))->on('people')->criteriaExp('surname,!LIKEEND,wn')->execCount());

		// BTW
		$this->assertEquals(4, (new Query('mysql'))->on('people')->criteriaExp('age,BTW,22,28')->execCount());
		$this->assertEquals(4, (new Query('mysql'))->on('people')->criteriaExp('age,BTW,:min,:max')->params(['min'=>22, 'max'=>28])->execCount());
		// !BTW
		$this->assertEquals(5, (new Query('mysql'))->on('people')->criteriaExp('age,!BTW,22,27')->execCount());
		$this->assertEquals(5, (new Query('mysql'))->on('people')->criteriaExp('age,!BTW,:min,:max')->params(['min'=>22, 'max'=>27])->execCount());

		// IN
		$this->assertEquals(3, (new Query('mysql'))->on('people')->criteriaExp('age,IN,21,22,23')->execCount());
		// !IN
		$this->assertEquals(5, (new Query('mysql'))->on('people')->criteriaExp('age,!IN,21,22,23')->execCount());
	}

	function testSetCriteriaDictionary() {
		$dictionary = [
			'ageGTE' => 'age >= ?1',
			'scoreXY' => 'score,BTW,?1,?2',
			'scoreXYbis' => 'score >= ?1 AND score <= ?2',
			'age2scoreSQL' => 'age = ?1 AND score = ?2',
			'age2scoreEXP' => 'age,EQ,?1|score,EQ,?2',
			'fullnameSQL' => '( name LIKE ?1 OR surname LIKE ?1 )'
		];
		// Dictionary SQL
		$this->assertEquals(4, (new Query('mysql'))->on('people')->setCriteriaDictionary($dictionary)->criteriaExp('fullnameSQL,%ee%')->execCount());
		$this->assertEquals(1, (new Query('mysql'))->on('people')->setCriteriaDictionary($dictionary)->criteriaExp('age2scoreSQL,17,25')->execCount());
		$this->assertEquals(1, (new Query('mysql'))->on('people')->setCriteriaDictionary($dictionary)->criteriaExp('age2scoreSQL,17,25|name,EQ,Don')->execCount());
		$this->assertEquals(0, (new Query('mysql'))->on('people')->setCriteriaDictionary($dictionary)->criteriaExp('age2scoreSQL,17,25|name,EQ,###')->execCount());
		// Dictionary EXP
		$this->assertEquals(1, (new Query('mysql'))->on('people')->setCriteriaDictionary($dictionary)->criteriaExp('age2scoreEXP,17,25')->execCount());
		$this->assertEquals(1, (new Query('mysql'))->on('people')->setCriteriaDictionary($dictionary)->criteriaExp('age2scoreEXP,17,25|name,EQ,Don')->execCount());
		$this->assertEquals(0, (new Query('mysql'))->on('people')->setCriteriaDictionary($dictionary)->criteriaExp('age2scoreEXP,17,25|name,EQ,###')->execCount());
		// mix with multiple translations
		$this->assertEquals(2, (new Query('mysql'))->on('people')->setCriteriaDictionary($dictionary)->criteriaExp('ageGTE,20|scoreXY,:min,:max')->params(['min'=>20, 'max'=>40])->execCount());
		$this->assertEquals(2, (new Query('mysql'))->on('people')->setCriteriaDictionary($dictionary)->criteriaExp('ageGTE,20|scoreXYbis,:min,:max')->params(['min'=>20, 'max'=>40])->execCount());
	}

	function testSetOrderByDictionary() {
		$dictionary = [
			'surnameASC' => 'surname ASC, name ASC',
			'surnameDESC' => 'surname DESC, name DESC'
		];
		$items = (new Query('mysql'))->on('people')->setOrderByDictionary($dictionary)->orderByExp('surname.ASC')->execSelect()->fetchAll(\PDO::FETCH_ASSOC);
		$this->assertEquals(6, $items[3]['id']);
		$this->assertEquals(8, $items[6]['id']);
		$items = (new Query('mysql'))->on('people')->setOrderByDictionary($dictionary)->orderByExp('surname.DESC')->execSelect()->fetchAll(\PDO::FETCH_ASSOC);
		$this->assertEquals(7, $items[3]['id']);
		$this->assertEquals(3, $items[6]['id']);
	}
}
