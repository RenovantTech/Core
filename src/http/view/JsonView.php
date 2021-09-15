<?php
namespace renovant\core\http\view;
use const renovant\core\trace\T_INFO;
use renovant\core\sys,
	renovant\core\http\Request,
	renovant\core\http\Response,
	renovant\core\http\Exception,
	renovant\core\http\ViewInterface;
class JsonView implements ViewInterface {

	const CONTENT_TYPE = 'application/json';

	/**
	 * @param Request $Req
	 * @param Response $Res
	 * @param null $resource
	 * @param array|null $options
	 * @throws Exception
	 */
	function render(Request $Req, Response $Res, $resource=null, array $options=null) {
		sys::trace(LOG_DEBUG, T_INFO, null, null, 'sys.http.JsonView->render');
		$Res->contentType(self::CONTENT_TYPE);
		echo json_encode($Res->getData(), $options);
		switch(json_last_error()) {
			case JSON_ERROR_NONE: break;
			case JSON_ERROR_DEPTH: throw new Exception(251); break;
			case JSON_ERROR_STATE_MISMATCH: throw new Exception(252); break;
			case JSON_ERROR_CTRL_CHAR: throw new Exception(253); break;
			case JSON_ERROR_SYNTAX: throw new Exception(254); break;
			case JSON_ERROR_UTF8: throw new Exception(255); break;
			default: throw new Exception(256);
		}
	}
}
