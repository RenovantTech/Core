<?php
namespace test\console\controller;
use renovant\core\console\Request,
	renovant\core\console\Response;

class ActionController extends \renovant\core\console\controller\ActionController {

	const FALLBACK_ACTION = 'fallback';

	function indexAction(Response $Res) {
		$Res->setView('index');
	}

	function fooAction(Response $Res) {
		$Res->setView('foo');
	}

	function barAction(Response $Res) {
		$Res->setView('bar');
	}

	/**
	 * @param Response $Res
	 * @param integer $id
	 */
	function action2Action(Response $Res, $id) {
		$Res->set('id', $id);
		$Res->setView('id-'.$id);
	}

	/**
	 * @param Response $Res
	 * @param string $name
	 */
	function action3Action(Response $Res, $name='Tom') {
		$Res->set('name', $name);
		$Res->setView('view3');
	}

	function fallbackAction(Request $Req, Response $Res) {

	}
}
