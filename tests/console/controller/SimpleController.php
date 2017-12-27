<?php
namespace test\console\controller;
use metadigit\core\console\Request,
	metadigit\core\console\Response;

class SimpleController implements \metadigit\core\console\ControllerInterface {

	function handle(Request $Req, Response $Res) {
		$Res->setView('index');
	}
}
