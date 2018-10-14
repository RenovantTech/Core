<?php
namespace test\http\controller;
use renovant\core\http\Request,
	renovant\core\http\Response;

class ActionController extends \renovant\core\http\controller\ActionController {

	function indexAction(Request $Req, Response $Res) {
		$Res->setView('index');
	}

	function fooAction(Request $Req, Response $Res) {
		$Res->setView('foo');
	}

	function barAction(Request $Req, Response $Res) {
		$Res->setView('bar');
	}

	/**
	 * @param \renovant\core\http\Request $Req
	 * @param \renovant\core\http\Response $Res
	 * @param integer $id
	 */
	function action2Action(Request $Req, Response $Res, $id) {
		$Res->set('id', $id)
			->setView('id-'.$id);
	}

	/**
	 * @param \renovant\core\http\Request $Req
	 * @param \renovant\core\http\Response $Res
	 * @param string $name
	 */
	function action3Action(Request $Req, Response $Res, $name='Tom') {
		$Res->set('name', $name)
			->setView('view3');
	}

	/**
	 * @routing(pattern="<day:\d{1,2}>/<month>/<year>/details-<format>")
	 * @param \renovant\core\http\Request $Req
	 * @param \renovant\core\http\Response $Res
	 * @param integer $year
	 * @param integer $month
	 * @param integer $day
	 * @param string $format
	 */
	function detailsAction(Request $Req, Response $Res, $year, $month, $day, $format) {
		$Res->set([
			'year' => $year,
			'month' => $month,
			'day' => $day,
			'format' => $format
		])->setView('details');
	}

	function ex13Action(Request $Req, Response $Res) {
		$Res->setView((new \StdClass));
	}
}
