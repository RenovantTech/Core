<?php
namespace mock\console\controller;
use metadigit\core\cli\Request,
	metadigit\core\cli\Response;

class ActionController extends \metadigit\core\console\controller\ActionController {

	const FALLBACK_ACTION = 'fallback';

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
	 * @param \metadigit\core\cli\Request $Req
	 * @param \metadigit\core\cli\Response $Res
	 * @param integer $id
	 */
	function action2Action(Request $Req, Response $Res, $id) {
		$Res->set('id', $id);
		$Res->setView('id-'.$id);
	}

	/**
	 * @param \metadigit\core\cli\Request $Req
	 * @param \metadigit\core\cli\Response $Res
	 * @param string $name
	 */
	function action3Action(Request $Req, Response $Res, $name='Tom') {
		$Res->set('name', $name);
		$Res->setView('view3');
	}

	function fallbackAction(Request $Req, Response $Res) {

	}
}