<?php
namespace test\http\session;
use metadigit\core\http\session\Manager;

class SessionTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$Session = new Manager;
		$this->assertInstanceOf(Manager::class, $Session);
//		$this->assertFileExists(\metadigit\core\DATA_DIR.'sessions.sqlite');
	}
}
