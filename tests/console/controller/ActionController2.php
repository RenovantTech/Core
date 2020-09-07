<?php
namespace test\console\controller;
use renovant\core\console\Request,
	renovant\core\console\Response;

class ActionController2 extends \renovant\core\console\controller\ActionController {

	function index(Request $Req, Response $Res) {
		$Res->setView('index');
	}
}
