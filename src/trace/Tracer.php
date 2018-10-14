<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\trace;
use renovant\core\sys;
/**
 * Tracer
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
class Tracer extends sys {

	const E_NOTICE = 1;
	const E_WARNING = 2;
	const E_ERROR = 3;

	/** current Error level, incremented by errors & exceptions
	 * @var integer */
	static protected $errorLevel = 0;

	/**
	 * Error handler
	 * @param integer $n       contains the level of the error raised
	 * @param string  $str     contains the error message
	 * @param string  $file    contains the filename that the error was raised in
	 * @param integer $line    contains the file line that the error was raised in
	 * @param array   $context contain an array of every variable that existed in the scope the error was triggered in
	 * @return void
	 */
	static function onError($n, $str, $file, $line, $context) {
//		if(error_reporting()===0) return;
		if(self::$errorLevel < self::E_NOTICE && in_array($n, [E_NOTICE,E_USER_NOTICE,E_STRICT])) self::$errorLevel = self::E_NOTICE;
		elseif(self::$errorLevel < self::E_WARNING && in_array($n, [E_WARNING,E_USER_WARNING])) self::$errorLevel = self::E_WARNING;
		else self::$errorLevel = self::E_ERROR;
		// get trace array, w/o first 2 elements (this function call)
		require_once __DIR__.'/functions.inc';
		traceError($n, $str, $file, $line);
		// @TODO call toDB() toLog() toEmail()
	}

	/**
	 * Exception handler
	 * @param \Throwable $Ex the Error/Exception raised
	 */
	static function onException(\Throwable $Ex) {
		$level = ($Ex instanceof \renovant\core\Exception) ? constant(get_class($Ex).'::LEVEL') : null;
		switch($level) {
			case E_USER_NOTICE: if(self::$errorLevel < self::E_NOTICE) self::$errorLevel = self::E_NOTICE; break;
			case E_USER_WARNING: if(self::$errorLevel < self::E_WARNING) self::$errorLevel = self::E_WARNING; break;
			default: self::$errorLevel = self::E_ERROR;
		}
		require_once __DIR__.'/functions.inc';
		traceException($Ex);
		// @TODO call toDB() toLog() toEmail()

	}

	/**
	 * Shutdown handler
	 */
	static function shutdown() {
		$err = error_get_last();
		if(in_array($err['type'], [E_ERROR,E_PARSE,E_CORE_ERROR,E_CORE_WARNING,E_COMPILE_ERROR,E_COMPILE_WARNING])) {
			self::onError($err['type'], $err['message'], $err['file'], $err['line'], null);
		}
		if(self::$Sys->cnfTrace['storeFn']) {
			call_user_func(self::$Sys->cnfTrace['storeFn'], self::$trace, self::$errorLevel);
		}
	}
	static function export() {
		return self::$trace;
	}
}
