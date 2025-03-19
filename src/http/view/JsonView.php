<?php
namespace renovant\core\http\view;

use renovant\core\sys;
use renovant\core\http\{Exception, Request, Response, ViewInterface};

use const renovant\core\trace\T_INFO;

class JsonView implements ViewInterface {
	public const CONTENT_TYPE = 'application/json';

	/**
	 * @param Request $Req
	 * @param Response $Res
	 * @param null $resource
	 * @param array|null $options
	 * @throws Exception
	 */
	public function render(Request $Req, Response $Res, $resource = null, array $options = null) {
		sys::trace(LOG_DEBUG, T_INFO, null, null, 'sys.http.JsonView->render');
		$Res->contentType(self::CONTENT_TYPE);
		echo json_encode($Res->getData());
		switch (json_last_error()) {
			case JSON_ERROR_NONE: break;
			case JSON_ERROR_DEPTH: throw new Exception(251);
			case JSON_ERROR_STATE_MISMATCH: throw new Exception(252);
			case JSON_ERROR_CTRL_CHAR: throw new Exception(253);
			case JSON_ERROR_SYNTAX: throw new Exception(254);
			case JSON_ERROR_UTF8: throw new Exception(255);
			default: throw new Exception(256);
		}
	}
}
