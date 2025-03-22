<?php
namespace renovant\core\trace;

use const renovant\core\DEBUG_MODE;

class Tracer {
	public const int E_NOTICE  = 1;
	public const int E_WARNING = 2;
	public const int E_ERROR   = 3;

	/** current Error level, incremented by errors & exceptions */
	protected static int $errorLevel = 0;

	/** @see set_error_handler() */
	public static function onError(int $n, string $str, string $file, int $line): void {
		//		if(error_reporting()===0) return;
		// get trace array, w/o first 2 elements (this function call)
		require_once __DIR__ . '/functions.inc.php';
		traceError($n, $str, $file, $line);
		// @TODO call toDB() toLog() toEmail()
		self::setErrorLevel($n);
	}

	/** @see set_exception_handler() */
	public static function onException(\Throwable $Ex) {
		$level = ($Ex instanceof \renovant\core\Exception) ? constant(get_class($Ex) . '::LEVEL') : null;
		require_once __DIR__ . '/functions.inc.php';
		traceException($Ex);
		// @TODO call toDB() toLog() toEmail()
		self::setErrorLevel($level);
	}

	/**
	 * Set TRACE ERROR level
	 */
	public static function setErrorLevel(?int $level) {
		switch ($level) {
			case E_NOTICE:
			case E_USER_NOTICE:
			case E_STRICT:
				if (self::$errorLevel < self::E_NOTICE) {
					self::$errorLevel = self::E_NOTICE;
				} break;
			case E_WARNING:
			case E_USER_WARNING:
				if (self::$errorLevel < self::E_WARNING) {
					self::$errorLevel = self::E_WARNING;
				} break;
			default:
				self::$errorLevel = self::E_ERROR;
		}
	}

	/**
	 * Shutdown handler
	 */
	public static function shutdown($Req, $Res, array $trace, $storeFn = null) {
		$err = error_get_last();
		if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING])) {
			self::onError($err['type'], $err['message'], $err['file'], $err['line']);
		}
		if (DEBUG_MODE) {
			TracerLog::write($Req, $Res, $trace, self::$errorLevel);
		}
		if ($storeFn) {
			call_user_func($storeFn, $Req, $Res, $trace, self::$errorLevel);
		}
	}
}
