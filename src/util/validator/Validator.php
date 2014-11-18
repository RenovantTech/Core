<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\util\validator;
use metadigit\core\Kernel;
/**
 * Validator
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Validator {

	static function validate($Object) {
		$class = get_class($Object);
		if(!$metadata = Kernel::getCache()->get('validator#'.$class)) {
			$metadata = (new ClassParser)->parse($class);
			Kernel::getCache()->set('validator#'.$class, $metadata, null, 'validator');
		}
		$errors = [];
		foreach($metadata['properties'] as $prop => $constraints) {
			$ReflProp = new \ReflectionProperty($class, $prop);
			$ReflProp->setAccessible(true);
			$value = $ReflProp->getValue($Object);
			if(in_array($prop, $metadata['nullable']) && is_null($value)) continue;
			foreach($constraints as $func => $param) {
				if(!Validator::$func($value, $param)) {
					$errors[$prop] = $func;
					TRACE and Kernel::trace(LOG_DEBUG, 0, __METHOD__, 'INVALID '.get_class($Object).'->'.$prop, $value.' NOT @validate('.$func.'="'.$param.'")');
				}
			}
		}
		return $errors;
	}

	// ====== basic constraints =====================================

	static function null($value) {
		return null === $value;
	}
	static function true($value) {
		return true === $value;
	}
	static function false($value) {
		return false === $value;
	}
	static function boolean($value) {
		return in_array($value, [true, false]);
	}

	// ====== string constraints ====================================

	static function email($value) {
		return (boolean) filter_var($value, FILTER_VALIDATE_EMAIL);
	}
	static function ip($value) {
		return (boolean) filter_var($value, FILTER_VALIDATE_IP);
	}
	static function length($value, $l) {
		return (strlen($value) == $l);
	}
	static function minLength($value, $l) {
		return (strlen($value) >= $l);
	}
	static function maxLength($value, $l) {
		return (strlen($value) <= $l);
	}
	static function regex($value, $regex) {
		return (boolean) preg_match($regex, $value);
	}
	static function URL($value) {
		return (boolean) filter_var($value, FILTER_VALIDATE_URL);
	}

	// ====== number constraints ====================================

	static function max($value, $i) {
		return ($value <= $i);
	}
	static function min($value, $i) {
		return ($value >= $i);
	}
	static function range($value, $range) {
		list($min, $max) = explode(',', $range);
		return ($value >= $min && $value <= $max);
	}

	// ====== other constraints =====================================

	static function callback($value, $callback) {
		return (boolean) call_user_func($callback, $value);
	}
}
