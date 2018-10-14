<?php
namespace test\http\session;
use renovant\core\http\session\Manager;

class SessionTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$Session = new Manager(null, [
			'class' => 'renovant\core\http\session\handler\Sqlite',
			'properties' => [
				'pdo' => 'sqlite',
				'table' => 'sys_auth_session'
			]
		]);
		$this->assertInstanceOf(Manager::class, $Session);
//		$this->assertFileExists(\renovant\core\DATA_DIR.'sessions.sqlite');
	}
}
