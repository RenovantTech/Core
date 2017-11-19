<?php
namespace test\session;
use metadigit\core\session\SessionManager;

class SessionManagerTest extends \PHPUnit\Framework\TestCase {

	function testConstructor() {
		$SessionManager = new SessionManager;
		$this->assertInstanceOf('metadigit\core\session\SessionManager', $SessionManager);
//		$this->assertFileExists(\metadigit\core\DATA_DIR.'sessions.sqlite');
	}
}
