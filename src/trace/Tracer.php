<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\trace;
use const metadigit\core\CORE_YAML;
use function metadigit\core\yaml;
use metadigit\core\Kernel;
/**
 * Tracer
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Tracer {

	const E_NOTICE = 1;
	const E_WARNING = 2;
	const E_ERROR = 3;

	static protected $conf = [
		'level' => LOG_DEBUG
	];
	/** current Error level, incremented by errors & exceptions
	 * @var integer */
	static protected $errorLevel = 0;
	/** backtrace store
	 * @var array */
	static protected $trace = [];
	/** backtrace current scope
	 * @var string */
	static protected $traceFn;

	static function init() {
		self::$conf = array_merge(self::$conf, yaml(CORE_YAML, 'trace'));
		if(is_string(self::$conf['level'])) self::$conf['level'] = constant(self::$conf['level']);
		set_exception_handler(__CLASS__.'::onException');
		set_error_handler(__CLASS__.'::onError');
	}

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
		$level = ($Ex instanceof \metadigit\core\Exception) ? constant(get_class($Ex).'::LEVEL') : null;
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
	 * @param integer $level trace level, use a LOG_* constant value
	 * @param integer $type trace type, use a T_* constant value
	 * @param string $msg the trace message
	 * @param mixed $data the trace data
	 * @param string $function the tracing object method / function
	 */
	static function trace($level=LOG_DEBUG, $type=T_INFO, $msg=null, $data=null, $function=null) {
		if($level > self::$conf['level']) return;
		$fn = str_replace('metadigit', '\\', $function?:self::$traceFn);
		self::$trace[] = [round(microtime(1)-$_SERVER['REQUEST_TIME_FLOAT'],5), memory_get_usage(), $level, $type, $fn, $msg, print_r($data,true)];
	}

	/**
	 * Setter/getter backtrace current scope
	 * @param string|null $fn
	 * @return string
	 */
	static function traceFn($fn=null) {
		if($fn) self::$traceFn = $fn;
		return self::$traceFn;
	}
	static function export() {
		return self::$trace;
	}
}
