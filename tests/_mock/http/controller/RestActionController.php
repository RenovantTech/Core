<?php
namespace mock\http\controller;
use metadigit\core\http\Request,
	metadigit\core\http\Response;

class RestActionController extends \metadigit\core\http\controller\ActionController {

	/**
	 * @routing(method="POST", pattern="<class>")
	 * @param Request $Req
	 * @param Response $Res
	 * @param string $class
	 */
	function createAction(Request $Req, Response $Res, $class) {
		$Res->set([
			'class' => $class,
			'id' => $Req->get('id')
		])->setView('create');
	}

	/**
	 * @routing(method="GET", pattern="<class>/<id>")
	 * @param Request $Req
	 * @param Response $Res
	 * @param string $class
	 * @param integer $id
	 */
	function readAction(Request $Req, Response $Res, $class, $id) {
		$Res->set([
			'class' => $class,
			'id' => $id
		])->setView('read');
	}

	/**
	 * @routing(method="GET", pattern="<class>")
	 * @param Request $Req
	 * @param Response $Res
	 * @param string $class
	 */
	function readAllAction(Request $Req, Response $Res, $class) {
		$Res->set('class', $class)
			->setView('readAll');
	}

	/**
	 * @routing(method="PUT", pattern="<class>/<id>")
	 * @param Request $Req
	 * @param Response $Res
	 * @param string $class
	 * @param integer $id
	 */
	function updateAction(Request $Req, Response $Res, $class, $id) {
		$Res->set([
			'class' => $class,
			'id' => $id
		])->setView('update');
	}

	/**
	 * @routing(method="DELETE", pattern="<class>/<id>")
	 * @param \metadigit\core\http\Request $Req
	 * @param \metadigit\core\http\Response $Res
	 * @param string $class
	 * @param integer $id
	 */
	function destroyAction(Request $Req, Response $Res, $class, $id) {
		$Res->set([
			'class' => $class,
			'id' => $id
		])->setView('destroy');
	}
}
