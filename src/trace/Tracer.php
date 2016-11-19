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
/**
 * Tracer
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Tracer {

	static protected $conf = [
		'level' => LOG_DEBUG
	];
	/** backtrace store
	 * @var array */
	static protected $trace = [];
	/** backtrace Error level, incremented by errors & exceptions
	 * @var integer */
	static public $traceError = 0;
	/** backtrace current scope
	 * @var string */
	static protected $traceFn;

	static function init() {
		self::$conf = array_merge(self::$conf,yaml(CORE_YAML, 'trace'));
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
Tracer::init();
