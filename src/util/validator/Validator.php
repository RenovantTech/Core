<?php
namespace renovant\core\util\validator;
use const renovant\core\SYS_CACHE;
use const renovant\core\trace\T_ERROR;
use renovant\core\sys;
class Validator {

	/**
	 * @param $Object
	 * @param array|null $validateSubset
	 * @return array
	 * @throws \ReflectionException
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
					sys::trace(LOG_DEBUG, T_ERROR, 'INVALID '.get_class($Object).'->'.$prop, $value.' NOT @validate('.$func.'="'.$param.'")', __METHOD__);
				}
			}
		}
		return $errors;
	}

	static protected function metadata($Object) {
		static $cache = [];
		$class = get_class($Object);
		if(isset($cache[$class])) return $cache[$class];
		$k = str_replace('\\','.',$class).':util:validator';
		if(!$cache[$class] = sys::cache(SYS_CACHE)->get($k)) {
			$cache[$class] = (new ClassParser)->parse($class);
			sys::cache(SYS_CACHE)->set($k, $cache[$class], null, 'util:validator');
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
	static function enum($value, $array) {
		return (boolean) in_array($value, $array);
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

	static function time($value) {
		return (boolean) preg_match('/^([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $value);
	}

	static function year($value) {
		return (boolean) preg_match('/^([0-9]{4})$/', $value);
	}

	// ====== other constraints =====================================

	static function callback($value, $callback) {
		return (boolean) call_user_func($callback, $value);
	}
}
