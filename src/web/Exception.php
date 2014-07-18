<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\web;
/**
 * MVC Exception
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Exception extends \metadigit\core\Exception {
	/* Dispatcher */
	const COD11 = 'Dispatcher Exception - impossible to detect controller for this URL/CMD: {1}';
	const COD12 = 'Dispatcher Exception - could not resolve view with name: {1}, resource: {2}';
	const COD13 = 'Dispatcher Exception - View neither contains a view name nor a View object';
	/* Controller - compile-time (user defined code checks) */
	const COD101 = 'Code Sintax Exception - Controller {1}->{2}() method MUST be declared "protected"';
	const COD102 = 'Code Sintax Exception - Controller method {1}->{2}(), parameter n°{3} must be of type {4}';
	/* Controller - run-time */
	const COD111 = 'Controller Exception - invalid handler method {1}->{2}()';
	/* View */
	const COD201 = 'View Exception - can not find resource, type: "{1}", requested path: {2}';
	const COD202 = 'View Exception - missing Response data: {1}';
	const COD203 = 'View Exception - wrong Response data: "{1}" must be of type {2}';
	const COD251 = 'JSON View Exception - Maximum stack depth exceeded';
	const COD252 = 'JSON View Exception - Underflow or the modes mismatch';
	const COD253 = 'JSON View Exception - Unexpected control character found';
	const COD254 = 'JSON View Exception - Syntax error, malformed JSON';
	const COD255 = 'JSON View Exception - Malformed UTF-8 characters, possibly incorrectly encoded';
	const COD256 = 'JSON View Exception - Unknown error';
}