<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\util\validator;
use function metadigit\core\{cache, trace};
/**
 * Validator
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Validator {

	/**
	 * @param $Object
	 * @param array|null $validateSubset
	 * @return array
	 */
	static function validate($Object, $validateSubset=null) {
		$class = get_class($Object);
		$metadata = self::metadata($Object);
		$errors = [];
		foreach($metadata['properties'] as $prop => $constraints) {
			if($validateSubset && !in_array($prop, $validateSubset)) continue;
			$ReflProp = new \ReflectionProperty($class, $prop);
			$ReflProp->setAccessible(true);
			$value = $ReflProp->getValue($Object);
			if(in_array($prop, $metadata['null']) && is_null($value)) continue;
			if(in_array($prop, $metadata['empty']) && empty($value)) continue;
			foreach($constraints as $func => $param) {
				if(!Validator::$func($value, $param)) {
					$errors[$prop] = $func;
					TRACE and trace(LOG_DEBUG, TRACE_ERROR, 'INVALID '.get_class($Object).'->'.$prop, $value.' NOT @validate('.$func.'="'.$param.'")', __METHOD__);
				}
			}
		}
		return $errors;
	}

	static protected function metadata($Object) {
		static $cache = [];
		$class = get_class($Object);
		if(isset($cache[$class])) return $cache[$class];
		$k = '#'.$class.'#validator';
		if(!$cache[$class] = cache('kernel')->get($k)) {
			$cache[$class] = (new ClassParser)->parse($class);
			cache('kernel')->set($k, $cache[$class], null, 'validator');
		}
		return $cache[$class];
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

	// ====== date & time constraints ===============================

	static function date($value) {
		return (boolean) preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', $value);
	}

	static function datetime($value) {
		return (boolean) preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $value);
	}

	static function year($value) {
		return (boolean) preg_match('/^([0-9]{4})$/', $value);
	}

	// ====== other constraints =====================================

	static function callback($value, $callback) {
		return (boolean) call_user_func($callback, $value);
	}
}
