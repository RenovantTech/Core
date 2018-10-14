<?php
namespace test\http\controller;
use renovant\core\http\Request,
	renovant\core\http\Response;

class SimpleController implements \renovant\core\http\ControllerInterface {

	function handle(Request $Req, Response $Res) {
		$Res->setView('index', null, 'PHP');
	}
}
