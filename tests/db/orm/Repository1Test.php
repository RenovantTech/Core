<?php
namespace test\db\orm;
use metadigit\core\Kernel,
	metadigit\core\context\Context,
	metadigit\core\db\orm\Exception,
	metadigit\core\db\orm\Repository,
	metadigit\core\util\DateTime;

class Repository1Test extends \PHPUnit_Framework_TestCase {

	static function setUpBeforeClass() {
		Kernel::pdo('mysql')->exec('
			DROP TABLE IF EXISTS `users`;
			DROP PROCEDURE IF EXISTS sp_users_insert;
			DROP PROCEDURE IF EXISTS sp_users_update;
			DROP PROCEDURE IF EXISTS sp_users_delete;
		');
		Kernel::pdo('mysql')->exec('
			CREATE TABLE IF NOT EXISTS `users` (
				id			smallint UNSIGNED NOT NULL AUTO_INCREMENT,
				active		tinyint(1) UNSIGNED NOT NULL,
				name		varchar(20),
				surname		varchar(20),
				age			tinyint UNSIGNED NOT NULL,
				score		decimal(4,2) UNSIGNED NOT NULL,
				email		varchar(30) NULL DEFAULT NULL,
				lastTime	datetime NULL,
				updatedAt	timestamp not NULL default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY(id)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		');
		Kernel::pdo('mysql')->exec('
			CREATE PROCEDURE sp_users_insert (
				IN p_name		varchar(20),
				IN p_surname	varchar(20),
				IN p_age		integer,
				IN p_score		decimal(4,2),
				OUT p_id		integer
			)
			BEGIN
				INSERT INTO users (name, surname, age, score) VALUES (p_name, p_surname, p_age, p_score);
				SET p_id = LAST_INSERT_ID();
			END;
		');
		Kernel::pdo('mysql')->exec('
			CREATE PROCEDURE sp_users_update (
				IN p_id			integer,
				IN p_name		varchar(20),
				IN p_surname	varchar(20),
				IN p_age		integer,
				IN p_score		decimal(4,2)
			)
			BEGIN
				UPDATE users SET name = p_name, surname = p_surname, age = p_age, score = p_score WHERE id = p_id;
			END;
		');
		Kernel::pdo('mysql')->exec('
			CREATE PROCEDURE sp_users_delete (
				IN p_id		integer
			)
			BEGIN
				DELETE FROM users WHERE id = p_id;
			END;
		');
	}

	static function tearDownAfterClass() {
		Kernel::pdo('mysql')->exec('
			DROP TABLE IF EXISTS `users`;
			DROP PROCEDURE IF EXISTS sp_users_insert;
			DROP PROCEDURE IF EXISTS sp_users_update;
			DROP PROCEDURE IF EXISTS sp_users_delete;
		');
	}

	protected function setUp() {
		Kernel::pdo('mysql')->exec('
			TRUNCATE TABLE `users`;
			INSERT INTO `users` (id, active, name, surname, age, score, lastTime) VALUES (1, 1, "Albert", "Brown", 21, 6.5, "2012-01-01 12:35:16");
			INSERT INTO `users` (id, active, name, surname, age, score, lastTime) VALUES (2, 1, "Barbara", "Yellow", 25, 7.1, "2012-02-15 18:40:00");
			INSERT INTO `users` (id, active, name, surname, age, score, lastTime) VALUES (3, 1, "Carl", "Green", 21, 5.8, "2012-03-15 18:40:00");
			INSERT INTO `users` (id, active, name, surname, age, score, lastTime) VALUES (4, 1, "Don", "Green", 17, 8.8, "2013-01-15 18:40:00");
			INSERT INTO `users` (id, active, name, surname, age, score, lastTime) VALUES (5, 1, "Emily", "Green", 18, 5.8, "2013-02-15 18:40:00");
			INSERT INTO `users` (id, active, name, surname, age, score, lastTime) VALUES (6, 1, "Franz", "Green", 28, 7.5, "2013-03-15 18:40:00");
			INSERT INTO `users` (id, active, name, surname, age, score, lastTime) VALUES (7, 1, "Gen", "Green", 25, 9, "2013-01-15 18:40:00");
			INSERT INTO `users` (id, active, name, surname, age, score, lastTime) VALUES (8, 1, "Hugh", "Green", 23, 7.2, "2013-02-15 18:40:00");
		');
	}

	function testConstructor() {
		$Context = Context::factory('mock.db.orm');
		$UsersRepository = new Repository('mock\db\orm\User', 'mysql');
		$UsersRepository->setContext($Context);
		$this->assertInstanceOf('metadigit\core\db\orm\Repository', $UsersRepository);
		return $UsersRepository;
	}

	/**
	 * @depends testConstructor
	 */
	function testCreate(Repository $UsersRepository) {
		$User = $UsersRepository->create(['name'=>'Tom', 'surname'=>'Brown']);
		$this->assertInstanceOf('mock\db\orm\User', $User);
		$this->assertEquals('Tom', $User->name);
		$this->assertEquals('Brown', $User->surname);
		$this->assertEquals('OPEN', $User->notORM);
	}

	/**
	 * @depends testConstructor
	 */
	function testDelete(Repository $UsersRepository) {
		// passing Entity
		$User = $UsersRepository->fetch(2);
		$this->assertInstanceOf('mock\db\orm\User', $UsersRepository->delete($User));
		$this->assertFalse($UsersRepository->fetch(2));

		// passing key
		$this->assertInstanceOf('mock\db\orm\User',$UsersRepository->delete(3));
		$this->assertFalse($UsersRepository->fetch(3));

		// test FETCH MODES

		$this->assertTrue($UsersRepository->delete(6, false));

		$data = $UsersRepository->delete(7, Repository::FETCH_ARRAY);
		$this->assertInternalType('array', $data);
		$this->assertSame('Gen', $data['name']);
		$this->assertSame('2013-01-15 18:40:00', $data['lastTime']->format('Y-m-d H:i:s'));

		$data = $UsersRepository->delete(8, Repository::FETCH_JSON);
		$this->assertInternalType('array', $data);
		$this->assertSame('Hugh', $data['name']);
		$this->assertSame('2013-02-15T18:40:00+00:00', $data['lastTime']);
	}

	/**
	 * @depends testConstructor
	 */
	function testDeleteAll(Repository $UsersRepository) {
		$this->assertSame(2, $UsersRepository->deleteAll(null, null, 'age,EQ,21'));
		$this->assertFalse($UsersRepository->fetch(1));
		$this->assertFalse($UsersRepository->fetch(3));

		$this->assertSame(3, $UsersRepository->deleteAll(3, 'age.ASC', 'age,GT,21'));
		$this->assertFalse($UsersRepository->fetch(2));
		$this->assertFalse($UsersRepository->fetch(7));
		$this->assertFalse($UsersRepository->fetch(8));
		$this->assertInstanceOf('mock\db\orm\User', $UsersRepository->fetch(6));
	}
	/**
	 * @depends testConstructor
	 */
	function testFetch(Repository $UsersRepository) {
		// FETCH_OBJ
		$User = $UsersRepository->fetch(1);
		$this->assertInstanceOf('mock\db\orm\User', $User);
		$this->assertSame(1, $User->id);
		$this->assertSame('Albert', $User->name);
		$this->assertSame('Brown', $User->surname);
		$this->assertSame(21, $User->age);
		$this->assertSame(6.5, $User->score);
		$this->assertEquals(new DateTime('2012-01-01 12:35:16'), $User->lastTime);

		// FETCH_ARRAY
		$userData = $UsersRepository->fetch(1, Repository::FETCH_ARRAY);
		$this->assertTrue(is_array($userData));
		$this->assertCount(9, $userData);
		$this->assertSame(1, $userData['id']);
		$this->assertSame('Albert', $userData['name']);
		$this->assertSame('Brown', $userData['surname']);
		$this->assertSame(21, $userData['age']);
		$this->assertSame(6.5, $userData['score']);
		$this->assertEquals(new DateTime('2012-01-01 12:35:16'), $userData['lastTime']);

		// FETCH_OBJ, with subset
		$User = $UsersRepository->fetch(1, Repository::FETCH_OBJ, 'mini');
		$this->assertInstanceOf('mock\db\orm\User', $User);
		$this->assertSame(1, $User->id);
		$this->assertSame('Albert', $User->name);
		$this->assertNull($User->surname); // excluded by subset
		$this->assertSame(20, $User->age); // excluded by subset, default value: 20
		$this->assertSame(6.5, $User->score);
		$this->assertNull($User->lastTime); // excluded by subset

		// FETCH_ARRAY, with subset
		$userData = $UsersRepository->fetch(1, Repository::FETCH_ARRAY, 'mini');
		$this->assertTrue(is_array($userData));
		$this->assertCount(3, $userData);
		$this->assertSame(1, $userData['id']);
		$this->assertSame('Albert', $userData['name']);
		$this->assertSame(6.5, $userData['score']);
	}

	/**
	 * @depends testConstructor
	 */
	function testFetchOne(Repository $UsersRepository) {
		// FETCH_OBJ
		$User = $UsersRepository->fetchOne(2, 'name ASC', 'age,LTE,18');
		$this->assertInstanceOf('mock\db\orm\User', $User);
		$this->assertSame(5, $User->id);
		$this->assertSame('Emily', $User->name);
		$this->assertSame('Green', $User->surname);

		// FETCH_ARRAY
		$entityData = $UsersRepository->fetchOne(2, 'name ASC', 'age,LTE,18', Repository::FETCH_ARRAY);
		$this->assertTrue(is_array($entityData));
		$this->assertCount(9, $entityData);
		$this->assertSame(5, $entityData['id']);
		$this->assertSame('Emily', $entityData['name']);
		$this->assertSame('Green', $entityData['surname']);

		// FETCH_OBJ, with subset
		$User = $UsersRepository->fetchOne(2, 'name ASC', 'age,LTE,18', Repository::FETCH_OBJ, 'mini');
		$this->assertInstanceOf('mock\db\orm\User', $User);
		$this->assertSame(5, $User->id);
		$this->assertSame('Emily', $User->name);
		$this->assertNull($User->surname); // excluded by subset
		$this->assertSame(20, $User->age); // excluded by subset, default value: 20
		$this->assertSame(5.8, $User->score);
		$this->assertNull($User->lastTime); // excluded by subset

		// FETCH_ARRAY, with subset
		$userData = $UsersRepository->fetchOne(2, 'name ASC', 'age,LTE,18', Repository::FETCH_ARRAY, 'mini');
		$this->assertTrue(is_array($userData));
		$this->assertCount(3, $userData);
		$this->assertSame(5, $userData['id']);
		$this->assertSame('Emily', $userData['name']);
		$this->assertSame(5.8, $userData['score']);

		// with Criteria Expression Dictionary
		$User = $UsersRepository->fetchOne(4, 'name ASC', 'activeAge,18');
		$this->assertInstanceOf('mock\db\orm\User', $User);
		$this->assertSame(5, $User->id);
		$this->assertSame('Emily', $User->name);
		$this->assertSame('Green', $User->surname);

		$User = $UsersRepository->fetchOne(2, 'name ASC', 'dateMonth,2013,02');
		$this->assertInstanceOf('mock\db\orm\User', $User);
		$this->assertSame(8, $User->id);
		$this->assertSame('Hugh', $User->name);
		$this->assertSame('Green', $User->surname);
	}

	/**
	 * @depends testConstructor
	 */
	function testFetchAll(Repository $UsersRepository) {
		// FETCH_OBJ
		$users = $UsersRepository->fetchAll(1, 20, 'name ASC, surname DESC', 'age,LTE,18|score,GTE,5');
		$this->assertCount(2, $users);
		$this->assertInstanceOf('mock\db\orm\User', $users[0]);
		$this->assertSame(4, $users[0]->id);
		$this->assertSame(5, $users[1]->id);

		// FETCH_ARRAY
		$users = $UsersRepository->fetchAll(1, 20, 'name ASC, surname DESC', 'age,LTE,18|score,GTE,5', Repository::FETCH_ARRAY);
		$this->assertCount(2, $users);
		$this->assertTrue(is_array($users[0]));
		$this->assertSame(4, $users[0]['id']);
		$this->assertSame(5, $users[1]['id']);

		// FETCH_OBJ, with subset
		$users = $UsersRepository->fetchAll(1, 20, 'name ASC, surname DESC', 'age,LTE,18|score,GTE,5', Repository::FETCH_OBJ, 'mini');
		$this->assertCount(2, $users);
		$this->assertInstanceOf('mock\db\orm\User', $users[1]);
		$this->assertSame(5, $users[1]->id);
		$this->assertSame('Emily', $users[1]->name);
		$this->assertNull($users[1]->surname); // excluded by subset
		$this->assertSame(20, $users[1]->age); // excluded by subset, default value: 20
		$this->assertSame(5.8, $users[1]->score);
		$this->assertNull($users[1]->lastTime); // excluded by subset

		// FETCH_ARRAY, with subset
		$users = $UsersRepository->fetchAll(1, 20, 'name ASC, surname DESC', 'age,LTE,18|score,GTE,5', Repository::FETCH_ARRAY, 'mini');
		$this->assertCount(2, $users);
		$this->assertTrue(is_array($users[1]));
		$this->assertCount(3, $users[1]);
		$this->assertSame(5, $users[1]['id']);
		$this->assertSame('Emily', $users[1]['name']);
		$this->assertSame(5.8, $users[1]['score']);

		// with Criteria Expression Dictionary
		$users = $UsersRepository->fetchAll(1, 20, 'name ASC', 'activeAge,18');
		$this->assertCount(7, $users);
		$users = $UsersRepository->fetchAll(1, 20, 'name ASC', 'dateMonth,2013,02');
		$this->assertCount(2, $users);

		// with OrderBy Dictionary
		$users = $UsersRepository->fetchAll(1, 20, 'age.DESC', 'surname,EQ,Green');
		$this->assertSame(6, $users[0]->id);
	}

	/**
	 * @depends testConstructor
	 */
	function testToArray(Repository $UsersRepository) {
		// no subset
		$User = $UsersRepository->fetch(1);
		$data = $UsersRepository->toArray($User);
		$this->assertCount(9, $data);
		$this->assertSame(1, $data['id']);
		$this->assertSame('Albert', $data['name']);
		$this->assertSame(6.5, $data['score']);

		// with subset
		$User = $UsersRepository->fetch(1, Repository::FETCH_OBJ, 'mini');
		$data = $UsersRepository->toArray($User, 'mini');
		$this->assertCount(3, $data);
		$this->assertSame(['id','name','score'], array_keys($data));

		// array of entities
		$users = $UsersRepository->fetchAll(1, 20, 'name ASC', 'age,EQ,21');
		$data = $UsersRepository->toArray($users);
		$this->assertCount(2, $data);
		$this->assertSame(1, $data[0]['id']);
		$this->assertSame(3, $data[1]['id']);
	}

	/**
	 * @depends testConstructor
	 */
	function testInsert(Repository $UsersRepository) {
		$lastTime = new DateTime();

		// INSERT full object
		$User9 = new \mock\db\orm\User(['name'=>'Zack', 'surname'=>'Orange', 'lastTime'=>$lastTime, 'email'=>'test@example.com', 'updatedAt'=>'2000-01-01 00:00:00']);
		$this->assertInstanceOf('mock\db\orm\User', $UsersRepository->insert($User9));
		$User9 = $UsersRepository->fetch(9);
		$this->assertInstanceOf('mock\db\orm\User', $User9);
		$this->assertSame(9, $User9->id);
		$this->assertSame('Zack', $User9->name);
		$this->assertSame('Orange', $User9->surname);
		$this->assertSame(20, $User9->age);
		$this->assertSame(1.0, $User9->score);
		$this->assertEquals($lastTime, $User9->lastTime);
		$this->assertNotEquals('2000-01-01 00:00:00', $User9->updatedAt->format('Y-m-d H:i:s'));

		// INSERT empty object passing values
		$User10 = new \mock\db\orm\User;
		$this->assertInstanceOf('mock\db\orm\User', $UsersRepository->insert($User10, [ 'name'=>'Zack', 'surname'=>'Johnson', 'email'=>'test@example.com', 'lastTime'=>date_create('2012-03-18 14:25:36') ]));
		$User10 = $UsersRepository->fetch(10);
		$this->assertInstanceOf('mock\db\orm\User', $User10);
		$this->assertSame(10, $User10->id);
		$this->assertSame('Zack', $User10->name);
		$this->assertSame('Johnson', $User10->surname);
		$this->assertSame('2012-03-18 14:25:36', $User10->lastTime->format('Y-m-d H:i:s'));

		// INSERT empty object passing values
		$User11 = new \mock\db\orm\User;
		$this->assertInstanceOf('mock\db\orm\User', $UsersRepository->insert($User11, [ 'name'=>'Zack', 'surname'=>'Johnson', 'email'=>'test@example.com', 'lastTime'=>null ]));
		$User11 = $UsersRepository->fetch(11);
		$this->assertInstanceOf('mock\db\orm\User', $User11);
		$this->assertSame(11, $User11->id);
		$this->assertSame('Zack', $User11->name);
		$this->assertSame('Johnson', $User11->surname);
		$this->assertNull($User11->lastTime);

		// INSERT null key & values
		$this->assertInstanceOf('mock\db\orm\User', $UsersRepository->insert(null, [ 'name'=>'Chao', 'surname'=>'Xing', 'email'=>'test@example.com', 'lastTime'=>null ]));
		$User12 = $UsersRepository->fetch(12);
		$this->assertInstanceOf('mock\db\orm\User', $User12);
		$this->assertSame(12, $User12->id);
		$this->assertSame('Chao', $User12->name);
		$this->assertSame('Xing', $User12->surname);
		$this->assertNull($User12->lastTime);

		// INSERT key & values
		$this->assertInstanceOf('mock\db\orm\User', $UsersRepository->insert(20, [ 'name'=>'Chao', 'surname'=>'Xing', 'email'=>'test@example.com', 'lastTime'=>null ]));
		$Entity20 = $UsersRepository->fetch(20);
		$this->assertInstanceOf('mock\db\orm\User', $Entity20);
		$this->assertSame(20, $Entity20->id);
		$this->assertSame('Chao', $Entity20->name);
		$this->assertSame('Xing', $Entity20->surname);
		$this->assertNull($Entity20->lastTime);

		// test FETCH MODES

		$this->assertTrue($UsersRepository->insert(21, [ 'name'=>'Chao', 'surname'=>'Xing', 'email'=>'test@example.com', 'lastTime'=>date_create('2012-03-18 14:25:36') ], true, false));

		$data = $UsersRepository->insert(22, [ 'name'=>'Chao', 'surname'=>'Xing', 'email'=>'test@example.com', 'lastTime'=>date_create('2012-03-18 14:25:36') ], true, Repository::FETCH_ARRAY);
		$this->assertInternalType('array', $data);
		$this->assertSame('Chao', $data['name']);
		$this->assertSame('2012-03-18 14:25:36', $data['lastTime']->format('Y-m-d H:i:s'));

		$data = $UsersRepository->insert(23, [ 'name'=>'Chao', 'surname'=>'Xing', 'email'=>'test@example.com', 'lastTime'=>date_create('2012-03-18 14:25:36') ], true, Repository::FETCH_JSON);
		$this->assertInternalType('array', $data);
		$this->assertSame('Chao', $data['name']);
		$this->assertSame('2012-03-18T14:25:36+00:00', $data['lastTime']);
	}

	/**
	 * @depends testConstructor
	 */
	function testInsertException(Repository $UsersRepository) {
		$lastTime = new DateTime();
		try {
			$UsersRepository->insert(null, ['name'=>'Zack', 'surname'=>'Orange', 'email'=>'test@', 'lastTime'=>$lastTime, 'updatedAt'=>'2000-01-01 00:00:00']);
		} catch(Exception $Ex) {
			$this->assertEquals(500, $Ex->getCode());
			$this->assertEquals('VALIDATION error: email', $Ex->getMessage());
			$this->assertEquals([ 'email'=>'email'], $Ex->getData());
		}
		try {
			$UsersRepository->insert(null, ['name'=>'Zack', 'surname'=>'Orange', 'age'=>10, 'lastTime'=>$lastTime, 'updatedAt'=>'2000-01-01 00:00:00']);
		} catch(Exception $Ex) {
			$this->assertEquals(500, $Ex->getCode());
			$this->assertEquals('VALIDATION error: age', $Ex->getMessage());
			$this->assertEquals([ 'age'=>'min'], $Ex->getData());
		}
	}

	/**
	 * @depends testConstructor
	 */
	function testDoValidate(Repository $UsersRepository) {

		// skip validation on "age"
		$data = ['name'=>'Zack', 'surname'=>'Orange', 'age'=>10];
		$User = $UsersRepository->insert(null, $data, 'main');
		$this->assertInstanceOf('mock\db\orm\User', $User);

		// skip validation on "name"
		$data = ['name'=>'Ugo', 'surname'=>'Orange', 'age'=>20];
		$User = $UsersRepository->insert(null, $data, 'extra');
		$this->assertInstanceOf('mock\db\orm\User', $User);

		// skip validation on "age", exception on "name"
		try {
			$data = ['name'=>'Ugo', 'surname'=>'Orange', 'age'=>10];
			$UsersRepository->insert(null, $data, 'main');
		} catch(Exception $Ex) {
			$this->assertEquals(500, $Ex->getCode());
			$this->assertEquals('VALIDATION error: name', $Ex->getMessage());
			$this->assertEquals([ 'name'=>'minLength'], $Ex->getData());
		}

		// skip validation on "name", exception on "age"
		try {
			$data = ['name'=>'Ugo', 'surname'=>'Orange', 'age'=>10];
			$UsersRepository->insert(null, $data, 'extra');
		} catch(Exception $Ex) {
			$this->assertEquals(500, $Ex->getCode());
			$this->assertEquals('VALIDATION error: age', $Ex->getMessage());
			$this->assertEquals([ 'age'=>'min'], $Ex->getData());
		}
	}

	/**
	 * @depends testConstructor
	 */
	function testInsertWithEmptyNull(Repository $UsersRepository) {
		$this->assertInstanceOf('mock\db\orm\User', $UsersRepository->insert(null, ['name'=>'Zack', 'surname'=>'Orange', 'email'=>'', 'lastTime'=>'', 'updatedAt'=>'2000-01-01 00:00:00']));
		$this->assertInstanceOf('mock\db\orm\User', $UsersRepository->insert(null, ['name'=>'Zack', 'surname'=>'Orange', 'email'=>null, 'lastTime'=>null, 'updatedAt'=>'2000-01-01 00:00:00']));
	}

	/**
	 * @depends testConstructor
	 */
	function testUpdate(Repository $UsersRepository) {
		// 1 - change Entity directly
		$User1 = $UsersRepository->fetch(1);
		$User1->surname = 'Brown2';
		$User1->updatedAt = '2000-01-01 00:00:00';
		$this->assertInstanceOf('mock\db\orm\User', $UsersRepository->update($User1));
		$User1 = $UsersRepository->fetch(1);
		$this->assertSame('Brown2', $User1->surname);
		$this->assertSame(7.5, $User1->score);
		$this->assertNotEquals('2000-01-01 00:00:00', $User1->updatedAt->format('Y-m-d H:i:s'));
		// 2 - pass new values array
		$this->assertInstanceOf('mock\db\orm\User', $UsersRepository->update(2, ['surname'=>'Yellow2']));
		$User2 = $UsersRepository->fetch(2);
		$this->assertSame('Yellow2', $User2->surname);
		$this->assertSame(8.1, $User2->score);
		// 2bis - pass new values array
		$User3 = $UsersRepository->fetch(3);
		$this->assertInstanceOf('mock\db\orm\User', $UsersRepository->update($User3, ['surname'=>'Green2']));
		$this->assertSame('Green2', $User3->surname);
		$this->assertSame(6.8, $User3->score);
		// 1+2 - change Entity & pass new values
		$User4 = $UsersRepository->fetch(4);
		$User4->surname = 'Green2';
		$User4->score = 1.2;
		$User4->lastTime = null;
		$this->assertInstanceOf('mock\db\orm\User', $UsersRepository->update($User4, ['name'=>'Don2']));
		$this->assertSame('Don2', $User4->name);
		$this->assertSame('Green2', $User4->surname);
		$this->assertSame(2.2, $User4->score);
		$this->assertNull($User4->lastTime);
		// test without re-fetch
		$User5 = $UsersRepository->fetch(5);
		$this->assertInstanceOf('mock\db\orm\User', $UsersRepository->update($User5, ['surname'=>'Johnson2', 'score'=>4.2]), ['fetch'=>false]);
		$this->assertSame('Johnson2', $User5->surname);
		$this->assertSame(5.2, $User5->score);

		// test FETCH MODES

		$this->assertTrue($UsersRepository->update(6, ['name'=>'Franz2', 'lastTime'=>date_create('2012-03-18 14:25:36')], true, false));

		$data = $UsersRepository->update(6, [ 'name'=>'Franz3', 'lastTime'=>date_create('2012-03-31 14:25:36') ], true, Repository::FETCH_ARRAY);
		$this->assertInternalType('array', $data);
		$this->assertSame('Franz3', $data['name']);
		$this->assertSame('2012-03-31 14:25:36', $data['lastTime']->format('Y-m-d H:i:s'));

		$data = $UsersRepository->update(6, [ 'name'=>'Franz4', 'lastTime'=>date_create('2012-02-02 16:10:36') ], true, Repository::FETCH_JSON);
		$this->assertInternalType('array', $data);
		$this->assertSame('Franz4', $data['name']);
		$this->assertSame('2012-02-02T16:10:36+00:00', $data['lastTime']);
	}

	/**
	 * @depends testConstructor
	 */
	function testUpdateException(Repository $UsersRepository) {
		try {
			$UsersRepository->update(1, ['name'=>'Albert2', 'surname'=>'Brown2', 'email'=>'test@']);
		} catch(Exception $Ex) {
			$this->assertEquals(500, $Ex->getCode());
			$this->assertEquals('VALIDATION error: email', $Ex->getMessage());
			$this->assertEquals([ 'email'=>'email'], $Ex->getData());
		}
		try {
			$UsersRepository->insert(1, ['name'=>'Albert2', 'surname'=>'Brown2', 'email'=>'test@', 'age'=>10]);
		} catch(Exception $Ex) {
			$this->assertEquals(500, $Ex->getCode());
			$this->assertEquals('VALIDATION error: age, email', $Ex->getMessage());
			$this->assertEquals([ 'age'=>'min', 'email'=>'email'], $Ex->getData());
		}
	}
}
