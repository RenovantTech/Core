<?php
namespace renovant\core\event;
class EventDispatcherException extends \renovant\core\Exception {
	// runtime
	const COD1 = '';
	const COD4 = '';
	// configuration
	const COD11 = '%s: namespace %s - YAML config file NOT FOUND';
	const COD12 = '%s: namespace %s - invalid YAML configuration';
}
