<?php
namespace mock\console\controller;
use metadigit\core\cli\Request,
	metadigit\core\cli\Response;

class SimpleController implements \metadigit\core\console\ControllerInterface {

	function handle(Request $Req, Response $Res) {
		$Res->setView('index');
	}
}