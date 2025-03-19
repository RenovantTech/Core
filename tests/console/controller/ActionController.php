<?php
namespace test\console\controller;

use renovant\core\console\Request,
renovant\core\console\Response;

class ActionController extends \renovant\core\console\controller\ActionController {
	public const FALLBACK_ACTION = 'fallback';

	public function index(Response $Res) {
		$Res->setView('index');
	}

	public function foo(Response $Res) {
		$Res->setView('foo');
	}

	public function bar(Response $Res) {
		$Res->setView('bar');
	}

	public function action2(Response $Res, int $id) {
		$Res->set('id', $id);
		$Res->setView('id-' . $id);
	}

	public function action3(Response $Res, string $name = 'Tom') {
		$Res->set('name', $name);
		$Res->setView('view3');
	}

	public function fallback(Request $Req, Response $Res) {
	}
}
