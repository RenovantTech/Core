<?php
namespace renovant\core\console;

class Exception extends \renovant\core\Exception {
	/* Dispatcher */
	public const COD11 = 'Dispatcher Exception - impossible to detect controller for this CMD: %s';
	public const COD12 = 'Dispatcher Exception - could not resolve view with name: %s, resource: %s';
	public const COD13 = 'Dispatcher Exception - View neither contains a view name nor a View object';
	/* Response */
	public const COD31 = 'Response Exception - setOutput() must set a writable stream resource';

	/* Controller - compile-time (user defined code checks) */
	public const COD101 = 'Code Sintax Exception - Controller %s->%s() method MUST be declared "protected"';
	public const COD102 = 'Code Sintax Exception - Controller method %s->%s(), parameter n°%s must be of type %s';
	/* Controller - run-time */
	public const COD111 = 'Controller Exception - invalid handler method %s->%s()';
	/* View */
	public const COD201 = 'View Exception - can not find resource, type: "%s", requested path: %s';
}
