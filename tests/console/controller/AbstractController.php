<?php
namespace test\console\controller;
use metadigit\core\console\Request,
	metadigit\core\console\Response;

class AbstractController extends \metadigit\core\console\controller\AbstractController {

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
