<?php
namespace test\console\controller;
use metadigit\core\console\Request,
	metadigit\core\console\Response;

class ActionController2 extends \metadigit\core\console\controller\ActionController {

	function indexAction(Request $Req, Response $Res) {
		$Res->setView('index');
	}
}
