<?php
namespace mock\console\controller;
use metadigit\core\cli\Request,
	metadigit\core\cli\Response;

class ActionController2 extends \metadigit\core\console\controller\ActionController {

	function indexAction(Request $Req, Response $Res) {
		$Res->setView('index');
	}
}