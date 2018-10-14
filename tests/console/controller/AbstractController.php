<?php
namespace test\console\controller;
use renovant\core\console\Request,
	renovant\core\console\Response;

class AbstractController extends \renovant\core\console\controller\AbstractController {

	/**
	 * @param Request $Req
	 * @param Response $Res
	 * @param string $name
	 */
	function doHandle(Request $Req, Response $Res, $name='Tom') {
		$Res->set('name', $name)
			->setView('view');
	}
}
