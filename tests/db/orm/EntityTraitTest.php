<?php
namespace test\db\orm;
use metadigit\core\util\DateTime,
	mock\db\orm\User,
	mock\db\orm\Stats;

class EntityTraitTest extends \PHPUnit_Framework_TestCase {

	function testConstructor() {
		$User = new User(['id'=>1, 'name'=>'Jack', 'surname'=>'Brown', 'age'=>21, 'scores'=>6.5, 'date'=>(new DateTime('2012-01-01 12:35:16'))]);
		$this->assertInstanceOf('mock\db\orm\User', $User);

		$Stats = new Stats(['code'=>'AA', 'year'=>'2014', 'scores'=>6.5]);
		$this->assertInstanceOf('mock\db\orm\Stats', $Stats);
	}
}