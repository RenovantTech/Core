<?php
namespace test\db\orm\util;
use metadigit\core\db\orm\Metadata;

class Repository1Test extends \PHPUnit_Framework_TestCase {

	function testArray2object() {
		$Metadata = new Metadata('mock\db\orm\User');

		$User = \metadigit\core\db\orm\util\DataMapper::array2object('mock\db\orm\User', ['name'=>'Zack', 'surname'=>'Johnson', 'age'=>0, 'email'=>'test@example.com', 'lastTime'=>date_create('2012-03-18 14:25:36')], $Metadata);
		$this->assertInstanceOf('mock\db\orm\User', $User);
		$this->assertSame(null, $User->id);
		$this->assertSame('Zack', $User->name);
		$this->assertSame('Johnson', $User->surname);
		$this->assertSame(0, $User->age);
		$this->assertSame('2012-03-18 14:25:36', $User->lastTime->format('Y-m-d H:i:s'));

		// check NULL properties

		$User = \metadigit\core\db\orm\util\DataMapper::array2object('mock\db\orm\User', [], $Metadata);
		$this->assertInstanceOf('mock\db\orm\User', $User);
		$this->assertSame(20, $User->age);
		$this->assertSame(null, $User->email);
		$this->assertSame(null, $User->lastTime);

		$User = \metadigit\core\db\orm\util\DataMapper::array2object('mock\db\orm\User', ['age'=>null, 'email'=>null, 'lastTime'=>null], $Metadata);
		$this->assertInstanceOf('mock\db\orm\User', $User);
		$this->assertSame(null, $User->age);
		$this->assertSame(null, $User->email);
		$this->assertSame(null, $User->lastTime);

		$User = \metadigit\core\db\orm\util\DataMapper::array2object('mock\db\orm\User', ['age'=>'', 'email'=>'', 'lastTime'=>''], $Metadata);
		$this->assertInstanceOf('mock\db\orm\User', $User);
		$this->assertSame(null, $User->age);
		$this->assertSame(null, $User->email);
		$this->assertSame(null, $User->lastTime);

		$User = \metadigit\core\db\orm\util\DataMapper::array2object('mock\db\orm\User', ['age'=>32, 'email'=>'test@example.com', 'lastTime'=>'2012-03-18 14:25:36'], $Metadata);
		$this->assertInstanceOf('mock\db\orm\User', $User);
		$this->assertSame(32, $User->age);
		$this->assertSame('test@example.com', $User->email);
		$this->assertSame('2012-03-18 14:25:36', $User->lastTime->format('Y-m-d H:i:s'));
	}
}
