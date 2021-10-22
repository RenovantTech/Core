<?php
namespace renovant\core\bin;
use renovant\core\sys;
class CmdController extends \renovant\core\console\controller\ActionController {

	/**
	 * @throws \ReflectionException
	 */
	function scan() {
		sys::cmd()->scan();
	}
}
