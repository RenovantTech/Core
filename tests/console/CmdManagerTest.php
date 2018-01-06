<?php
namespace test\console;
use metadigit\core\console\CmdManager;

class CmdManagerTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @return CmdManager
	 */
	function testConstruct() {
		$CmdManager = new CmdManager('sqlite', 'sys_cmd');
		$this->assertInstanceOf(CmdManager::class, $CmdManager);
		return $CmdManager;
	}

	/**
	 * @depends testConstruct
	 * @param CmdManager $CmdManager
	 */
	function testExec(CmdManager $CmdManager) {
		$pid = $CmdManager->start('sys');
		$this->assertInternalType(\PHPUnit\Framework\Constraint\IsType::TYPE_INT, $pid);
	}
}
