<?php
namespace renovant\core\console;
class Exception extends \renovant\core\Exception {
	/* Dispatcher */
	const COD11 = 'Dispatcher Exception - impossible to detect controller for this CMD: %s';
	const COD12 = 'Dispatcher Exception - could not resolve view with name: %s, resource: %s';
	const COD13 = 'Dispatcher Exception - View neither contains a view name nor a View object';
	/* Response */
	const COD31 = 'Response Exception - setOutput() must set a writable stream resource';

	/* Controller - compile-time (user defined code checks) */
	const COD101 = 'Code Sintax Exception - Controller %s->%s() method MUST be declared "protected"';
	const COD102 = 'Code Sintax Exception - Controller method %s->%s(), parameter n°%s must be of type %s';
	/* Controller - run-time */
	const COD111 = 'Controller Exception - invalid handler method %s->%s()';
	/* View */
	const COD201 = 'View Exception - can not find resource, type: "%s", requested path: %s';
}
