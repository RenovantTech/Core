<?php
namespace test\console\controller;
use metadigit\core\cli\Request,
	metadigit\core\cli\Response;

class AbstractController extends \metadigit\core\console\controller\AbstractController {

	/**
	 * @param \metadigit\core\cli\Request $Req
	 * @param \metadigit\core\cli\Response $Res
	 * @param string $name
	 */
	function doHandle(Request $Req, Response $Res, $name='Tom') {
		$Res->set('name', $name)
			->setView('view');
	}
}
