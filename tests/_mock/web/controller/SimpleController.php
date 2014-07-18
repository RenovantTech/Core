<?php
namespace mock\web\controller;
use metadigit\core\http\Request,
	metadigit\core\http\Response;

class SimpleController implements \metadigit\core\web\ControllerInterface {

	function handle(Request $Req, Response $Res) {
		$Res->setView('index');
	}
}