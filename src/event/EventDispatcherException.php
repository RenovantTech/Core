<?php
namespace renovant\core\event;

class EventDispatcherException extends \renovant\core\Exception {
	// runtime
	public const COD1 = '';
	public const COD4 = '';
	// configuration
	public const COD11 = '%s: context %s - YAML config file NOT FOUND';
	public const COD12 = '%s: context %s - invalid YAML configuration';
}
