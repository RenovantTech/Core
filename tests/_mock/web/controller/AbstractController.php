<?php
namespace mock\web\controller;
use metadigit\core\http\Request,
	metadigit\core\http\Response;

class AbstractController extends \metadigit\core\web\controller\AbstractController {

	/**
	 * @routing <categ>/<tags>/<id>
	 * @param \metadigit\core\http\Request $Req
	 * @param \metadigit\core\http\Response $Res
	 * @param string $categ
	 * @param string $tags
	 * @param integer $id
	 * @return string
	 */
	function doHandle(Request $Req, Response $Res, $categ, $tags, $id=1) {
		$Res->set([
			'categ' => $categ,
			'tags' => $tags,
			'id' => $id
		])->setView('view');
	}
}