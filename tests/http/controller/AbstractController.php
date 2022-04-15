<?php
namespace test\http\controller;
use renovant\core\http\Request,
	renovant\core\http\Response;

class AbstractController extends \renovant\core\http\controller\AbstractController {

	/**
	 * @routing <categ>/<tags>/<id>
	 */
	function doHandle(Request $Req, Response $Res, string $categ, string $tags, int $id=1) {
		$Res->set([
			'categ' => $categ,
			'tags' => $tags,
			'id' => $id
		])->setView('view');
	}
}
