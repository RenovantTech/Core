<?php
namespace mock\http\controller;
use metadigit\core\http\Request,
	metadigit\core\http\Response;

class SimpleController implements \metadigit\core\http\ControllerInterface {

	function handle(Request $Req, Response $Res) {
		$Res->setView('index');
	}
}
