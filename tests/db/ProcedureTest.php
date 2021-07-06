<?php
namespace test\db;
use renovant\core\sys,
	renovant\core\db\Procedure;

class ProcedureTest extends \PHPUnit\Framework\TestCase {

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


	function testExec() {
		$SqlProcedure = (new Procedure('sp_people', 'mysql'));
		$data = $SqlProcedure->exec(['name'=>'Xiao', 'surname'=>'Ming', 'age'=>25, 'score'=>30, '@id', '@time']);
		$this->assertEquals(9, $data['id']);
	}
}
