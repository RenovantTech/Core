<?php
namespace test\http\controller;
use renovant\core\http\Request,
	renovant\core\http\Response;

class ActionController extends \renovant\core\http\controller\ActionController {

	function index(Request $Req, Response $Res) {
		$Res->setView('index');
	}

	function foo(Request $Req, Response $Res) {
		$Res->setView('foo');
	}

	function bar(Request $Req, Response $Res) {
		$Res->setView('bar');
	}

	function action2(Request $Req, Response $Res, int $id) {
		$Res->set('id', $id)
			->setView('id-'.$id);
	}

	function action3(Request $Req, Response $Res, string $name='Tom') {
		$Res->set('name', $name)
			->setView('view3');
	}

	/**
	 * @routing(pattern="<day:\d{1,2}>/<month>/<year>/details-<format>")
	 */
	function details(Request $Req, Response $Res, int $year, int $month, int $day, string $format) {
		$Res->set([
			'year' => $year,
			'month' => $month,
			'day' => $day,
			'format' => $format
		])->setView('details');
	}

	function ex13(Request $Req, Response $Res) {
		$Res->setView((new \StdClass));
	}
}
