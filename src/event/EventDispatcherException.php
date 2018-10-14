<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\event;
/**
 * EventDispatcherException
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
class EventDispatcherException extends \renovant\core\Exception {
	// runtime
	const COD1 = '';
	const COD4 = '';
	// configuration
	const COD11 = '%s: namespace %s - YAML config file NOT FOUND';
	const COD12 = '%s: namespace %s - invalid YAML configuration';
}
