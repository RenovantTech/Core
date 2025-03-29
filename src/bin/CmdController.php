<?php
namespace renovant\core\bin;

use renovant\core\sys;

class CmdController extends \renovant\core\console\controller\ActionController {
	/**
	 * @throws \ReflectionException
	 */
	public function batchScan() {
		sys::cmd()->scan();
	}
}
