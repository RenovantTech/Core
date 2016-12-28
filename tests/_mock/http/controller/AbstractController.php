<?php
namespace mock\http\controller;
use metadigit\core\http\Request,
	metadigit\core\http\Response;

class AbstractController extends \metadigit\core\http\controller\AbstractController {

	/**
	 * @routing <categ>/<tags>/<id>
	 * @param \metadigit\core\http\Request $Req
	 * @param \metadigit\core\http\Response $Res
	 * @param string $categ
	 * @param string $tags
	 * @param integer $id
	 */
	function doHandle(Request $Req, Response $Res, $categ, $tags, $id=1) {
		$Res->set([
			'categ' => $categ,
			'tags' => $tags,
			'id' => $id
		])->setView('view');
	}
}
