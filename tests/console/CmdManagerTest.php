<?php
namespace test\console;

use renovant\core\console\{CmdManager, Exception};

use const renovant\core\{BIN_DIR, DATA_DIR};

class CmdManagerTest extends \PHPUnit\Framework\TestCase {
	/**
	 * @return CmdManager
	 */
	public function testConstruct() {
		$CmdManager = new CmdManager('mysql', 'sys_cmd');
		$this->assertInstanceOf(CmdManager::class, $CmdManager);
		return $CmdManager;
	}

	/**
	 * @depends testConstruct
	 * @param CmdManager $CmdManager
	 */
	public function testExec(CmdManager $CmdManager) {
		$CmdManager->exec('sys');
		//		$this->assertEquals('CMD OUTPUT', file_get_contents(DATA_DIR . 'sys-cmd.output'));
	}

	/**
	 * @depends testConstruct
	 */
	public function testExecException1(CmdManager $CmdManager) {
		try {
			$CmdManager->exec('not-exists');
			$this->fail('Expected Exception not thrown');
		} catch (Exception $Ex) {
			$this->assertEquals(1, $Ex->getCode());
			$this->assertEquals('CLI launcher file ' . BIN_DIR . 'not-exists' . ' not found', $Ex->getMessage());
		}
	}

	/**
	 * @depends testConstruct
	 */
	public function testExecException2(CmdManager $CmdManager) {
		try {
			$CmdManager->exec('not-executable');
			$this->fail('Expected Exception not thrown');
		} catch (Exception $Ex) {
			$this->assertEquals(2, $Ex->getCode());
			$this->assertEquals('CLI launcher file ' . BIN_DIR . 'not-executable' . ' is not executable', $Ex->getMessage());
		}
	}
}
