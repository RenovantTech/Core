<?php
namespace test\db\orm;
use metadigit\core\util\DateTime,
	mock\db\orm\User,
	mock\db\orm\Stats;

class EntityTraitTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$User = new User(['id'=>1, 'name'=>'Jack', 'surname'=>'Brown', 'age'=>21, 'scores'=>6.5, 'date'=>(new DateTime('2012-01-01 12:35:16'))]);
		$this->assertInstanceOf('mock\db\orm\User', $User);

		$Stats = new Stats(['code'=>'AA', 'year'=>'2014', 'scores'=>6.5]);
		$this->assertInstanceOf('mock\db\orm\Stats', $Stats);
	}

	function testInvoke() {
		$User = new User(['name'=>'Zack', 'surname'=>'Johnson', 'age'=>0, 'email'=>'test@example.com', 'lastTime'=>'2012-03-18 14:25:36']);
		$this->assertInstanceOf('mock\db\orm\User', $User);
		$this->assertSame(null, $User->id);
		$this->assertSame('Zack', $User->name);
		$this->assertSame('Johnson', $User->surname);
		$this->assertSame(0, $User->age);
		$this->assertSame('2012-03-18 14:25:36', $User->lastTime->format('Y-m-d H:i:s'));

		// check NULL properties

		$User = new User();
		$this->assertInstanceOf('mock\db\orm\User', $User);
		$this->assertSame(20, $User->age);
		$this->assertSame(null, $User->email);
		$this->assertSame(null, $User->lastTime);

		$User = new User(['age'=>null, 'email'=>null, 'lastTime'=>null]);
		$this->assertInstanceOf('mock\db\orm\User', $User);
		$this->assertSame(null, $User->age);
		$this->assertSame(null, $User->email);
		$this->assertSame(null, $User->lastTime);

		$User = new User(['age'=>'', 'email'=>'', 'lastTime'=>'']);
		$this->assertInstanceOf('mock\db\orm\User', $User);
		$this->assertSame(null, $User->age);
		$this->assertSame(null, $User->email);
		$this->assertSame(null, $User->lastTime);

		$User = new User(['age'=>32, 'email'=>'test@example.com', 'lastTime'=>'2012-03-18 14:25:36']);
		$this->assertInstanceOf('mock\db\orm\User', $User);
		$this->assertSame(32, $User->age);
		$this->assertSame('test@example.com', $User->email);
		$this->assertSame('2012-03-18 14:25:36', $User->lastTime->format('Y-m-d H:i:s'));
	}

	function testSet() {
		$User = new User();
		// string
		$User->name = 'Zack';
		$this->assertSame('Zack', $User->name);
		// integer
		$User->age = 18;
		$this->assertSame(18, $User->age);
		$User->age = '18aaa';
		$this->assertSame(18, $User->age);
		// float
		$User->score = 18;
		$this->assertSame(18.0, $User->score);
		$User->score = '18.22aaa';
		$this->assertSame(18.22, $User->score);
		// boolean
		$User->active = 0;
		$this->assertSame(false, $User->active);
		$User->active = 'aaa';
		$this->assertSame(true, $User->active);
		// datetime
		$User->lastTime = '2012-03-18 14:25:36';
		$this->assertSame('2012-03-18 14:25:36', $User->lastTime->format('Y-m-d H:i:s'));
	}
}
