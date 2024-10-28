<?php
namespace renovant\core\util\validator;

use renovant\core\sys;

use const renovant\core\SYS_CACHE;
use const renovant\core\trace\T_ERROR;

class Validator {
	/**
	 * @param $Object
	 * @param array|null $validateSubset
	 * @return array
	 * @throws \ReflectionException
	 */
	public static function validate($Object, ?array $validateSubset = null): array {
		$class    = get_class($Object);
		$metadata = self::metadata($Object);
		$errors   = [];
		foreach ($metadata['properties'] as $prop => $constraints) {
			if ($validateSubset && !in_array($prop, $validateSubset)) {
				continue;
			}
			$ReflProp = new \ReflectionProperty($class, $prop);
			$ReflProp->setAccessible(true);
			$value = $ReflProp->getValue($Object);
			if (in_array($prop, $metadata['null']) && is_null($value)) {
				continue;
			}
			if (in_array($prop, $metadata['empty']) && empty($value)) {
				continue;
			}
			foreach ($constraints as $func => $param) {
				if (!Validator::$func($value, $param)) {
					$errors[$prop] = $func;
					sys::trace(LOG_DEBUG, T_ERROR, 'INVALID ' . get_class($Object) . '->' . $prop, $value . ' NOT @validate(' . $func . '="' . $param . '")', __METHOD__);
				}
			}
		}
		return $errors;
	}

	/**
	 * @throws \ReflectionException
	 */
	protected static function metadata($Object) {
		static $cache = [];
		$class        = get_class($Object);
		if (isset($cache[$class])) {
			return $cache[$class];
		}
		$k = str_replace('\\', '.', $class) . ':util:validator';
		if (!$cache[$class] = sys::cache(SYS_CACHE)->get($k)) {
			$cache[$class] = (new ClassParser())->parse($class);
			sys::cache(SYS_CACHE)->set($k, $cache[$class], 0, 'util:validator');
		}
		return $cache[$class];
	}

	// ====== basic constraints =====================================

	public static function null($value): bool {
		return null === $value;
	}

	public static function true($value): bool {
		return true === $value;
	}

	public static function false($value): bool {
		return false === $value;
	}

	public static function boolean($value): bool {
		return in_array($value, [true, false]);
	}

	// ====== string constraints ====================================

	public static function email($value): bool {
		return (bool) filter_var($value, FILTER_VALIDATE_EMAIL);
	}

	public static function enum($value, $array): bool {
		$array = array_map('trim', explode(',', $array));
		return in_array($value, $array);
	}

	public static function ip($value): bool {
		return (bool) filter_var($value, FILTER_VALIDATE_IP);
	}

	public static function length($value, $l): bool {
		return (strlen($value) == $l);
	}

	public static function minLength($value, $l): bool {
		return (strlen($value) >= $l);
	}

	public static function maxLength($value, $l): bool {
		return (strlen($value) <= $l);
	}

	public static function regex($value, $regex): bool {
		return (bool) preg_match($regex, $value);
	}

	public static function URL($value): bool {
		return (bool) filter_var($value, FILTER_VALIDATE_URL);
	}

	// ====== number constraints ====================================

	public static function max($value, $i): bool {
		return ($value <= $i);
	}

	public static function min($value, $i): bool {
		return ($value >= $i);
	}

	public static function range($value, $range): bool {
		list($min, $max) = explode(',', $range);
		return ($value >= $min && $value <= $max);
	}

	// ====== date & time constraints ===============================

	public static function date($value): bool {
		return (bool) preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', $value);
	}

	public static function datetime($value): bool {
		return (bool) preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $value);
	}

	public static function time($value): bool {
		return (bool) preg_match('/^([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $value);
	}

	public static function year($value): bool {
		return (bool) preg_match('/^([0-9]{4})$/', $value);
	}

	// ====== other constraints =====================================

	public static function callback($value, $callback): bool {
		return (bool) call_user_func($callback, $value);
	}
}
