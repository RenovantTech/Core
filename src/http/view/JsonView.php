<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\http\view;
use const renovant\core\trace\T_INFO;
use renovant\core\sys,
	renovant\core\http\Request,
	renovant\core\http\Response,
	renovant\core\http\Exception,
	renovant\core\http\ViewInterface;
/**
 * Json View
 * It outputs JSON formatted data to the client.
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
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
