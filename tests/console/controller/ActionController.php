<?php
namespace test\console\controller;
use renovant\core\console\Request,
	renovant\core\console\Response;

class ActionController extends \renovant\core\console\controller\ActionController {

	const FALLBACK_ACTION = 'fallback';

	function index(Response $Res) {
		$Res->setView('index');
	}

	function foo(Response $Res) {
		$Res->setView('foo');
	}

	function bar(Response $Res) {
		$Res->setView('bar');
	}

	/**
	 * @param Response $Res
	 * @param integer $id
	 */
	function action2(Response $Res, $id) {
		$Res->set('id', $id);
		$Res->setView('id-'.$id);
	}

	/**
	 * @param Response $Res
	 * @param string $name
	 */
	function action3(Response $Res, $name='Tom') {
		$Res->set('name', $name);
		$Res->setView('view3');
	}

	function fallback(Request $Req, Response $Res) {

	}
}
