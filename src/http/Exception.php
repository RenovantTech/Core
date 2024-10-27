<?php
namespace renovant\core\http;

class Exception extends \renovant\core\Exception {
	/* Dispatcher */
	public const COD11 = 'Dispatcher Exception - impossible to detect controller for this URL: %s';
	public const COD12 = 'Dispatcher Exception - View Engine "%s" not valid, must be resolved to a class implementing ViewInterface';
	/* Controller - compile-time (user defined code checks) */
	public const COD101 = 'Code Syntax Exception - Controller %s->%s() method MUST be declared "protected"';
	/* Controller - run-time */
	public const COD111 = 'Controller Exception - invalid handler method %s->%s()';
	/* View - run-time */
	public const COD201 = 'View Exception - can not find resource, type: "%s", requested path: %s';
	public const COD202 = 'View Exception - missing Response data: %s';
	public const COD203 = 'View Exception - wrong Response data: "%s" must be of type %s';
	public const COD251 = 'JSON View Exception - Maximum stack depth exceeded';
	public const COD252 = 'JSON View Exception - Underflow or the modes mismatch';
	public const COD253 = 'JSON View Exception - Unexpected control character found';
	public const COD254 = 'JSON View Exception - Syntax error, malformed JSON';
	public const COD255 = 'JSON View Exception - Malformed UTF-8 characters, possibly incorrectly encoded';
	public const COD256 = 'JSON View Exception - Unknown error';
	public const COD261 = 'X-SendFile View Exception: constant XSENDFILE_PATH not defined';
	public const COD262 = 'X-SendFile View Exception: constant XSENDFILE_URL not defined';
	/* CryptoCookie */
	public const COD401 = 'CryptoCookie Exception - Decryption failed';
	public const COD402 = 'CryptoCookie SodiumException - Decryption failed';
}
