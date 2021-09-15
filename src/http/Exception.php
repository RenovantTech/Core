<?php
namespace renovant\core\http;
class Exception extends \renovant\core\Exception {
	/* Dispatcher */
	const COD11 = 'Dispatcher Exception - impossible to detect controller for this URL: %s';
	const COD12 = 'Dispatcher Exception - View Engine "%s" not valid, must be resolved to a class implementing ViewInterface';
	/* Controller - compile-time (user defined code checks) */
	const COD101 = 'Code Syntax Exception - Controller %s->%s() method MUST be declared "protected"';
	/* Controller - run-time */
	const COD111 = 'Controller Exception - invalid handler method %s->%s()';
	/* View - run-time */
	const COD201 = 'View Exception - can not find resource, type: "%s", requested path: %s';
	const COD202 = 'View Exception - missing Response data: %s';
	const COD203 = 'View Exception - wrong Response data: "%s" must be of type %s';
	const COD251 = 'JSON View Exception - Maximum stack depth exceeded';
	const COD252 = 'JSON View Exception - Underflow or the modes mismatch';
	const COD253 = 'JSON View Exception - Unexpected control character found';
	const COD254 = 'JSON View Exception - Syntax error, malformed JSON';
	const COD255 = 'JSON View Exception - Malformed UTF-8 characters, possibly incorrectly encoded';
	const COD256 = 'JSON View Exception - Unknown error';
	const COD261 = 'X-SendFile View Exception: constant XSENDFILE_PATH not defined';
	const COD262 = 'X-SendFile View Exception: constant XSENDFILE_URL not defined';
}
