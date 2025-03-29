<?php
namespace renovant\core\console;

class Exception extends \renovant\core\Exception {
	/* executable */
	public const string COD1 = 'CLI launcher file %s not found';
	public const string COD2 = 'CLI launcher file %s is not executable';

	/* Dispatcher */
	public const string COD11 = 'Dispatcher Exception - impossible to detect controller for this CMD: %s';
	public const string COD12 = 'Dispatcher Exception - could not resolve view with name: %s, resource: %s';
	public const string COD13 = 'Dispatcher Exception - View neither contains a view name nor a View object';
	/* Response */
	public const string COD31 = 'Response Exception - setOutput() must set a writable stream resource';

	/* Controller - compile-time (user defined code checks) */
	public const string COD101 = 'Code Sintax Exception - Controller %s->%s() method MUST be declared "protected"';
	public const string COD102 = 'Code Sintax Exception - Controller method %s->%s(), parameter n°%s must be of type %s';
	/* Controller - run-time */
	public const string COD111 = 'Controller Exception - invalid handler method %s->%s()';
	/* View */
	public const string COD201 = 'View Exception - can not find resource, type: "%s", requested path: %s';
}
