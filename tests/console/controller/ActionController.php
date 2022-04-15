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

	function action2(Response $Res, int $id) {
		$Res->set('id', $id);
		$Res->setView('id-'.$id);
	}

	function action3(Response $Res, string $name='Tom') {
		$Res->set('name', $name);
		$Res->setView('view3');
	}

	function fallback(Request $Req, Response $Res) {

	}
}
