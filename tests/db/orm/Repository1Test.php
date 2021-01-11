<?php
namespace test\db\orm;
use renovant\core\db\orm\OrmEvent;
use renovant\core\event\EventDispatcher;
use renovant\core\sys,
	renovant\core\acl\ACL,
	renovant\core\db\orm\Exception,
	renovant\core\db\orm\Repository,
	renovant\core\util\DateTime,
	test\acl\ACLTest;

class Repository1Test extends \PHPUnit\Framework\TestCase {

	static function setUpBeforeClass():void {
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS `users`;
			DROP PROCEDURE IF EXISTS sp_users_insert;
			DROP PROCEDURE IF EXISTS sp_users_update;
			DROP PROCEDURE IF EXISTS sp_users_delete;
		');
		sys::pdo('mysql')->exec('
			CREATE TABLE IF NOT EXISTS `users` (
				id			smallint UNSIGNED NOT NULL AUTO_INCREMENT,
				active		tinyint(1) UNSIGNED NOT NULL,
				name		varchar(20),
				surname		varchar(20),
				age			tinyint UNSIGNED NOT NULL,
				birthday	date NULL DEFAULT NULL,
				score		decimal(4,2) UNSIGNED NOT NULL,
				email		varchar(30) NULL DEFAULT NULL,
				lastTime	datetime NULL DEFAULT NULL,
				updatedAt	timestamp not NULL default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY(id)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		');
		sys::pdo('mysql')->exec('
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
		sys::pdo('mysql')->exec('
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
		sys::pdo('mysql')->exec('
			CREATE PROCEDURE sp_users_delete (
				IN p_id		integer
			)
			BEGIN
				DELETE FROM users WHERE id = p_id;
			END;
		');
		ACLTest::setUpBeforeClass();
		new ACL(['ORM'], 'mysql');
	}

	static function tearDownAfterClass():void {
		sys::pdo('mysql')->exec('
			DROP TABLE IF EXISTS `users`;
			DROP PROCEDURE IF EXISTS sp_users_insert;
			DROP PROCEDURE IF EXISTS sp_users_update;
			DROP PROCEDURE IF EXISTS sp_users_delete;
		');
		ACLTest::tearDownAfterClass();
	}

	protected function setUp():void {
		sys::pdo('mysql')->exec('
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

	/**
	 * @return object|null
	 * @throws \ReflectionException
	 * @throws \renovant\core\container\ContainerException
	 */
	function testConstructor() {
		$UsersRepository = sys::context()->container()->get('test.db.orm.UserRepository');
		$this->assertInstanceOf('renovant\core\db\orm\Repository', $UsersRepository);
		return $UsersRepository;
	}

	/**
	 * @depends testConstructor
	 * @param Repository $UsersRepository
	 */
	function testCreate(Repository $UsersRepository) {
		$User = $UsersRepository->create(['name'=>'Tom', 'surname'=>'Brown']);
		$this->assertInstanceOf('test\db\orm\User', $User);
		$this->assertEquals('Tom', $User->name);
		$this->assertEquals('Brown', $User->surname);
		$this->assertEquals('OPEN', $User->notORM);
	}

	/**
	 * @depends testConstructor
	 * @param Repository $UsersRepository
	 * @throws Exception
	 */
	function testDelete(Repository $UsersRepository) {
		// passing Entity
		$User = $UsersRepository->fetch(2);
		$this->assertInstanceOf('test\db\orm\User', $UsersRepository->delete($User));
		$this->assertFalse($UsersRepository->fetch(2));

		// passing key
		$this->assertInstanceOf('test\db\orm\User', $UsersRepository->delete(3));
		$this->assertFalse($UsersRepository->fetch(3));

		// test FETCH MODES

		$this->assertTrue($UsersRepository->delete(6, false));

		$data = $UsersRepository->delete(7, Repository::FETCH_ARRAY);
		$this->assertIsArray($data);
		$this->assertSame('Gen', $data['name']);
		$this->assertSame('2013-01-15 18:40:00', $data['lastTime']->format('Y-m-d H:i:s'));

		$data = $UsersRepository->delete(8, Repository::FETCH_JSON);
		$this->assertIsArray($data);
		$this->assertSame('Hugh', $data['name']);
		$this->assertSame('2013-02-15T18:40:00+00:00', $data['lastTime']);
	}

	/**
	 * @depends testConstructor
	 * @param Repository $UsersRepository
	 * @throws Exception
	 */
	function testDeleteAll(Repository $UsersRepository) {
		$this->assertSame(2, $UsersRepository->deleteAll(null, null, 'age,EQ,21'));
		$this->assertFalse($UsersRepository->fetch(1));
		$this->assertFalse($UsersRepository->fetch(3));

		$this->assertSame(3, $UsersRepository->deleteAll(3, 'age.ASC', 'age,GT,21'));
		$this->assertFalse($UsersRepository->fetch(2));
		$this->assertFalse($UsersRepository->fetch(7));
		$this->assertFalse($UsersRepository->fetch(8));
		$this->assertInstanceOf('test\db\orm\User', $UsersRepository->fetch(6));
	}

	/**
	 * @depends testConstructor
	 * @param Repository $UsersRepository
	 * @throws Exception
	 */
	function testFetch(Repository $UsersRepository) {
		// FETCH_OBJ
		$User = $UsersRepository->fetch(1);
		$this->assertInstanceOf('test\db\orm\User', $User);
		$this->assertSame(1, $User->id);
		$this->assertSame('Albert', $User->name);
		$this->assertSame('Brown', $User->surname);
		$this->assertSame(21, $User->age);
		$this->assertSame(6.5, $User->score);
		$this->assertEquals(new DateTime('2012-01-01 12:35:16'), $User->lastTime);

		// FETCH_ARRAY
		$userData = $UsersRepository->fetch(1, Repository::FETCH_ARRAY);
		$this->assertTrue(is_array($userData));
		$this->assertCount(10, $userData);
		$this->assertSame(1, $userData['id']);
		$this->assertSame('Albert', $userData['name']);
		$this->assertSame('Brown', $userData['surname']);
		$this->assertSame(21, $userData['age']);
		$this->assertSame(6.5, $userData['score']);
		$this->assertEquals(new DateTime('2012-01-01 12:35:16'), $userData['lastTime']);

		// FETCH_OBJ, with subset
		$User = $UsersRepository->fetch(1, Repository::FETCH_OBJ, 'mini');
		$this->assertInstanceOf('test\db\orm\User', $User);
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
	 * @param Repository $UsersRepository
	 * @throws Exception
	 */
	function testFetchOne(Repository $UsersRepository) {
		// FETCH_OBJ
		$User = $UsersRepository->fetchOne(2, 'name ASC', 'age,LTE,18');
		$this->assertInstanceOf('test\db\orm\User', $User);
		$this->assertSame(5, $User->id);
		$this->assertSame('Emily', $User->name);
		$this->assertSame('Green', $User->surname);

		// FETCH_ARRAY
		$entityData = $UsersRepository->fetchOne(2, 'name ASC', 'age,LTE,18', Repository::FETCH_ARRAY);
		$this->assertTrue(is_array($entityData));
		$this->assertCount(10, $entityData);
		$this->assertSame(5, $entityData['id']);
		$this->assertSame('Emily', $entityData['name']);
		$this->assertSame('Green', $entityData['surname']);

		// FETCH_OBJ, with subset
		$User = $UsersRepository->fetchOne(2, 'name ASC', 'age,LTE,18', Repository::FETCH_OBJ, 'mini');
		$this->assertInstanceOf('test\db\orm\User', $User);
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
		$this->assertInstanceOf('test\db\orm\User', $User);
		$this->assertSame(5, $User->id);
		$this->assertSame('Emily', $User->name);
		$this->assertSame('Green', $User->surname);
		$User = $UsersRepository->fetchOne(2, 'name ASC', 'dateMonth,2013,02');
		$this->assertInstanceOf('test\db\orm\User', $User);
		$this->assertSame(8, $User->id);
		$this->assertSame('Hugh', $User->name);
		$this->assertSame('Green', $User->surname);

		// offset
		$User = $UsersRepository->fetchOne(1, 'name ASC', 'activeAge,18');
		$this->assertInstanceOf('test\db\orm\User', $User);
		$this->assertSame(1, $User->id);
		$User = $UsersRepository->fetchOne(null, 'name ASC', 'activeAge,18');
		$this->assertInstanceOf('test\db\orm\User', $User);
		$this->assertSame(1, $User->id);
	}

	/**
	 * @depends testConstructor
	 * @param Repository $UsersRepository
	 * @throws Exception
	 */
	function testFetchAll(Repository $UsersRepository) {
		// FETCH_OBJ
		$users = $UsersRepository->fetchAll(1, 20, 'name ASC, surname DESC', 'age,LTE,18|score,GTE,5');
		$this->assertCount(2, $users);
		$this->assertInstanceOf('test\db\orm\User', $users[0]);
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
		$this->assertInstanceOf('test\db\orm\User', $users[1]);
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

		// page & pageSize
		$users = $UsersRepository->fetchAll(null, null, 'name.DESC');
		$this->assertCount(8, $users);
		$users = $UsersRepository->fetchAll(1, 4, 'name.DESC', null, Repository::FETCH_ARRAY);
		$this->assertCount(4, $users);
		$this->assertSame(8, $users[0]['id']);
		$users = $UsersRepository->fetchAll(2, 4, 'name.DESC', null, Repository::FETCH_ARRAY);
		$this->assertCount(4, $users);
		$this->assertSame(4, $users[0]['id']);
	}

	/**
	 * @depends testConstructor
	 * @param Repository $UsersRepository
	 * @throws Exception
	 */
	function testToArray(Repository $UsersRepository) {
		// no subset
		$User = $UsersRepository->fetch(1);
		$data = $UsersRepository->toArray($User);
		$this->assertCount(10, $data);
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
	 * @param Repository $UsersRepository
	 * @throws Exception
	 * @throws \Exception
	 */
	function testDoValidate(Repository $UsersRepository) {

		// skip validation on "age"
		$data = ['name'=>'Zack', 'surname'=>'Orange', 'age'=>10];
		$User = $UsersRepository->insertOne(null, $data, 'main');
		$this->assertInstanceOf('test\db\orm\User', $User);

		// skip validation on "name"
		$data = ['name'=>'Ugo', 'surname'=>'Orange', 'age'=>20];
		$User = $UsersRepository->insertOne(null, $data, 'extra');
		$this->assertInstanceOf('test\db\orm\User', $User);

		// skip validation on "age", exception on "name"
		try {
			$data = ['name'=>'Ugo', 'surname'=>'Orange', 'age'=>10];
			$UsersRepository->insertOne(null, $data, 'main');
		} catch(Exception $Ex) {
			$this->assertEquals(500, $Ex->getCode());
			$this->assertEquals('VALIDATION error: name', $Ex->getMessage());
			$this->assertEquals([ 'name'=>'minLength'], $Ex->getData());
		}

		// skip validation on "name", exception on "age"
		try {
			$data = ['name'=>'Ugo', 'surname'=>'Orange', 'age'=>10];
			$UsersRepository->insertOne(null, $data, 'extra');
		} catch(Exception $Ex) {
			$this->assertEquals(500, $Ex->getCode());
			$this->assertEquals('VALIDATION error: age', $Ex->getMessage());
			$this->assertEquals([ 'age'=>'min'], $Ex->getData());
		}
	}

	/**
	 * @depends testConstructor
	 * @param Repository $UsersRepository
	 * @throws Exception
	 */
	function testInsert(Repository $UsersRepository) {
		$lastTime = new DateTime();

		// INSERT object
		$User9 = new \test\db\orm\User(['name'=>'Zack', 'surname'=>'Orange', 'lastTime'=>$lastTime, 'email'=>'test@example.com', 'updatedAt'=>date_create('2000-01-01 00:00:00')]);
		$this->assertInstanceOf('test\db\orm\User', $UsersRepository->insert($User9));
		$User9 = $UsersRepository->fetch(9);
		$this->assertInstanceOf('test\db\orm\User', $User9);
		$this->assertSame(9, $User9->id);
		$this->assertSame('Zack', $User9->name);
		$this->assertSame('Orange', $User9->surname);
		$this->assertSame(20, $User9->age);
		$this->assertSame(1.0, $User9->score);
		$this->assertEquals($lastTime->format('Y-m-d H:i:s'), $User9->lastTime->format('Y-m-d H:i:s'));
		$this->assertNotEquals('2000-01-01 00:00:00', $User9->updatedAt->format('Y-m-d H:i:s'));

		// INSERT object with key
		$User21 = new \test\db\orm\User([ 'id'=>21, 'name'=>'Zack', 'surname'=>'Johnson', 'email'=>'test@example.com', 'lastTime'=>date_create('2012-03-18 14:25:36') ]);
		$this->assertInstanceOf('test\db\orm\User', $UsersRepository->insert($User21));
		$User21 = $UsersRepository->fetch(21);
		$this->assertInstanceOf('test\db\orm\User', $User21);
		$this->assertSame(21, $User21->id);
		$this->assertSame('Zack', $User21->name);
		$this->assertSame('Johnson', $User21->surname);
		$this->assertSame('2012-03-18 14:25:36', $User21->lastTime->format('Y-m-d H:i:s'));
	}

	/**
	 * @depends testConstructor
	 * @param Repository $UsersRepository
	 * @throws Exception
	 */
	function testInsertOne(Repository $UsersRepository) {
		$lastTime = new DateTime();

		// INSERT null key & values
		$this->assertInstanceOf('test\db\orm\User', $UsersRepository->insertOne(null, [ 'name'=>'Chao', 'surname'=>'Xing', 'email'=>'test@example.com', 'lastTime'=>null ]));
		$User9 = $UsersRepository->fetch(9);
		$this->assertInstanceOf('test\db\orm\User', $User9);
		$this->assertSame(9, $User9->id);
		$this->assertSame('Chao', $User9->name);
		$this->assertSame('Xing', $User9->surname);
		$this->assertNull($User9->lastTime);


		// INSERT key & values
		$this->assertInstanceOf('test\db\orm\User', $UsersRepository->insertOne(22, [ 'name'=>'Chao', 'surname'=>'Xing', 'email'=>'test@example.com', 'lastTime'=>null ]));
		$User22 = $UsersRepository->fetch(22);
		$this->assertInstanceOf('test\db\orm\User', $User22);
		$this->assertSame(22, $User22->id);
		$this->assertSame('Chao', $User22->name);
		$this->assertSame('Xing', $User22->surname);
		$this->assertNull($User22->lastTime);

		// test FETCH MODES

		$this->assertTrue($UsersRepository->insertOne(null, [ 'name'=>'Chao', 'surname'=>'Xing', 'email'=>'test@example.com', 'lastTime'=>'2012-03-18 14:25:36' ], true, false));

		$User = $UsersRepository->insertOne(null, [ 'name'=>'Chao', 'surname'=>'Xing', 'email'=>'test@example.com', 'lastTime'=>'2012-03-18 14:25:36' ], true, Repository::FETCH_OBJ);
		$this->assertInstanceOf('test\db\orm\User', $User);
		$this->assertSame(24, $User->id);
		$this->assertSame('Chao', $User->name);
		$this->assertSame('2012-03-18 14:25:36', $User->lastTime->format('Y-m-d H:i:s'));

		$data = $UsersRepository->insertOne(null, [ 'name'=>'Chao', 'surname'=>'Xing', 'email'=>'test@example.com', 'lastTime'=>'2012-03-18 14:25:36' ], true, Repository::FETCH_ARRAY);
		$this->assertIsArray($data);
		$this->assertSame(25, $data['id']);
		$this->assertSame('Chao', $data['name']);
		$this->assertSame('2012-03-18 14:25:36', $data['lastTime']->format('Y-m-d H:i:s'));

		$data = $UsersRepository->insertOne(null, [ 'name'=>'Chao', 'surname'=>'Xing', 'email'=>'test@example.com', 'lastTime'=>'2012-03-18 14:25:36' ], true, Repository::FETCH_JSON);
		$this->assertIsArray($data);
		$this->assertSame(26, $data['id']);
		$this->assertSame('Chao', $data['name']);
		$this->assertSame('2012-03-18T14:25:36+00:00', $data['lastTime']);
	}

	/**
	 * @depends testConstructor
	 * @param Repository $UsersRepository
	 * @throws \Exception
	 */
	function testInsertException(Repository $UsersRepository) {
		$lastTime = new DateTime();
		try {
			$UsersRepository->insertOne(null, ['name'=>'Zack', 'surname'=>'Orange', 'email'=>'test@', 'lastTime'=>$lastTime, 'updatedAt'=>'2000-01-01 00:00:00']);
		} catch(Exception $Ex) {
			$this->assertEquals(500, $Ex->getCode());
			$this->assertEquals('VALIDATION error: email', $Ex->getMessage());
			$this->assertEquals([ 'email'=>'email'], $Ex->getData());
		}
		try {
			$UsersRepository->insertOne(null, ['name'=>'Zack', 'surname'=>'Orange', 'age'=>10, 'lastTime'=>$lastTime, 'updatedAt'=>'2000-01-01 00:00:00']);
		} catch(Exception $Ex) {
			$this->assertEquals(500, $Ex->getCode());
			$this->assertEquals('VALIDATION error: age', $Ex->getMessage());
			$this->assertEquals([ 'age'=>'min'], $Ex->getData());
		}
	}

	/**
	 * @depends testConstructor
	 * @param Repository $UsersRepository
	 * @throws Exception
	 */
	function testInsertWithEmptyNull(Repository $UsersRepository) {
		$this->assertInstanceOf('test\db\orm\User', $UsersRepository->insertOne(null, ['name'=>'Zack', 'surname'=>'Orange', 'email'=>'', 'lastTime'=>'', 'updatedAt'=>'2000-01-01 00:00:00']));
		$this->assertInstanceOf('test\db\orm\User', $UsersRepository->insertOne(null, ['name'=>'Zack', 'surname'=>'Orange', 'email'=>null, 'lastTime'=>null, 'updatedAt'=>'2000-01-01 00:00:00']));
	}


	/**
	 * @depends testConstructor
	 * @param Repository $UsersRepository
	 * @throws Exception
	 */
	function testUpdate(Repository $UsersRepository) {
		$User1 = $UsersRepository->fetch(1);
		$User1->surname = 'Brown2';
		$User1->updatedAt = '2000-01-01 00:00:00';
		$User1->lastTime = null;
		$this->assertInstanceOf('test\db\orm\User', $UsersRepository->update($User1));
		$User1 = $UsersRepository->fetch(1);
		$this->assertSame('Brown2', $User1->surname);
		$this->assertSame(7.5, $User1->score);
		$this->assertNotEquals('2000-01-01 00:00:00', $User1->updatedAt->format('Y-m-d H:i:s'));
		$this->assertNull($User1->lastTime);
	}

	/**
	 * @depends testConstructor
	 * @param Repository $UsersRepository
	 * @throws Exception
	 */
	function testUpdateEvents(Repository $UsersRepository) {
		$RefProp = new \ReflectionProperty('renovant\core\sys', 'EventDispatcher');
		$RefProp->setAccessible(true);
		$EventDispatcher = $RefProp->getValue();

		$eventFn = function (OrmEvent $Event) {
			$User = $Event->getEntity();
			$this->assertInstanceOf('test\db\orm\User', $User);
		};
		$EventDispatcher->listen('USERS:UPDATING', $eventFn);

		$User = $UsersRepository->fetch(1);
		$User->name = 'Zack2';
		$this->assertInstanceOf('test\db\orm\User', $UsersRepository->update($User));
		$this->assertSame('Zack2', $User->name);
	}

	/**
	 * @depends testConstructor
	 * @param Repository $UsersRepository
	 * @throws Exception
	 */
	function testUpdateOne(Repository $UsersRepository) {

		// 1 - pass new values array
		$this->assertInstanceOf('test\db\orm\User', $UsersRepository->updateOne(2, ['surname'=>'Yellow2']));
		$User2 = $UsersRepository->fetch(2);
		$this->assertSame('Yellow2', $User2->surname);
		$this->assertSame(8.1, $User2->score);

		// 2 - skipped update (same values)
		$this->assertInstanceOf('test\db\orm\User', $UsersRepository->updateOne(2, ['surname'=>'Yellow2']));

		// test FETCH MODES

		$this->assertTrue($UsersRepository->updateOne(6, ['name'=>'Franz2', 'lastTime'=>'2012-03-18 14:25:36'], true, false));

		$data = $UsersRepository->updateOne(6, [ 'name'=>'Franz3', 'lastTime'=>'2012-03-31 14:25:36' ], true, Repository::FETCH_ARRAY);
		$this->assertIsArray($data);
		$this->assertSame('Franz3', $data['name']);
		$this->assertSame('2012-03-31 14:25:36', $data['lastTime']->format('Y-m-d H:i:s'));

		$data = $UsersRepository->updateOne(6, [ 'name'=>'Franz4', 'lastTime'=>'2012-02-02 16:10:36' ], true, Repository::FETCH_JSON);
		$this->assertIsArray($data);
		$this->assertSame('Franz4', $data['name']);
		$this->assertSame('2012-02-02T16:10:36+00:00', $data['lastTime']);
	}

	/**
	 * @depends testConstructor
	 * @param Repository $UsersRepository
	 * @throws \Exception
	 */
	function testUpdateException(Repository $UsersRepository) {
		try {
			$UsersRepository->updateOne(1, ['name'=>'Albert2', 'surname'=>'Brown2', 'email'=>'test@']);
		} catch(Exception $Ex) {
			$this->assertEquals(500, $Ex->getCode());
			$this->assertEquals('VALIDATION error: email', $Ex->getMessage());
			$this->assertEquals([ 'email'=>'email'], $Ex->getData());
		}
		try {
			$UsersRepository->insertOne(1, ['name'=>'Albert2', 'surname'=>'Brown2', 'email'=>'test@', 'age'=>10]);
		} catch(Exception $Ex) {
			$this->assertEquals(500, $Ex->getCode());
			$this->assertEquals('VALIDATION error: age, email', $Ex->getMessage());
			$this->assertEquals([ 'age'=>'min', 'email'=>'email'], $Ex->getData());
		}
	}
}
