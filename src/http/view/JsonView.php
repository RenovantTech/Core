<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\http\view;
use const metadigit\core\trace\T_INFO;
use metadigit\core\sys,
	metadigit\core\http\Request,
	metadigit\core\http\Response,
	metadigit\core\http\Exception;
/**
 * Json View
 * It outputs JSON formatted data to the client.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class JsonView implements \metadigit\core\http\ViewInterface {
	use \metadigit\core\CoreTrait;

	const CONTENT_TYPE = 'application/json';

	function render(Request $Req, Response $Res, $resource=null, array $options=null) {
		sys::trace(LOG_DEBUG, T_INFO);
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
