<?php
namespace test\console\controller;
use renovant\core\console\Request,
	renovant\core\console\Response;

class SimpleController implements \renovant\core\console\ControllerInterface {

	function handle(Request $Req, Response $Res) {
		$Res->setView('index');
	}
}
