<?php
namespace test\auth\session;
use metadigit\core\auth\session\Session;

class SessionTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$Session = new Session;
		$this->assertInstanceOf('metadigit\core\auth\session\Session', $Session);
//		$this->assertFileExists(\metadigit\core\DATA_DIR.'sessions.sqlite');
	}
}
