<?php
namespace test\http\controller;
use renovant\core\http\Request,
	renovant\core\http\Response;

class AbstractController extends \renovant\core\http\controller\AbstractController {

	/**
	 * @routing <categ>/<tags>/<id>
	 * @param \renovant\core\http\Request $Req
	 * @param \renovant\core\http\Response $Res
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
