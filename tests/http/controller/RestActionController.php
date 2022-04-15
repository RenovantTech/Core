<?php
namespace test\http\controller;
use renovant\core\http\Request,
	renovant\core\http\Response;

class RestActionController extends \renovant\core\http\controller\ActionController {

	/**
	 * @routing(method="POST", pattern="<class>")
	 */
	function create(Request $Req, Response $Res, string $class) {
		$Res->set([
			'class' => $class,
			'id' => $Req->get('id')
		])->setView('create');
	}

	/**
	 * @routing(method="GET", pattern="<class>/<id>")
	 */
	function read(Request $Req, Response $Res, string $class, int $id) {
		$Res->set([
			'class' => $class,
			'id' => $id
		])->setView('read');
	}

	/**
	 * @routing(method="GET", pattern="<class>")
	 */
	function readAll(Request $Req, Response $Res, string $class) {
		$Res->set('class', $class)
			->setView('readAll');
	}

	/**
	 * @routing(method="PUT", pattern="<class>/<id>")
	 */
	function update(Request $Req, Response $Res, string $class, int $id) {
		$Res->set([
			'class' => $class,
			'id' => $id
		])->setView('update');
	}

	/**
	 * @routing(method="DELETE", pattern="<class>/<id>")
	 */
	function destroy(Request $Req, Response $Res, string $class, int $id) {
		$Res->set([
			'class' => $class,
			'id' => $id
		])->setView('destroy');
	}
}
