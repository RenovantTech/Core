<?php
namespace test\console\controller;
use renovant\core\console\Request,
	renovant\core\console\Response;

class AbstractController extends \renovant\core\console\controller\AbstractController {

	function doHandle(Request $Req, Response $Res, string $name='Tom') {
		$Res->set('name', $name)
			->setView('view');
	}
}
