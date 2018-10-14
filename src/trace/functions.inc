<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace metadigit\core\trace;
use metadigit\core\sys;

const LABELS = [
		E_ERROR=>'E_ERROR',
		E_WARNING=>'E_WARNING',
		E_PARSE=>'E_PARSE',
		E_NOTICE=>'E_NOTICE',
		E_CORE_ERROR=>'E_CORE_ERROR',
		E_CORE_WARNING=>'E_CORE_WARNING',
		E_COMPILE_ERROR=>'E_COMPILE_ERROR',
		E_COMPILE_WARNING=>'E_COMPILE_WARNING',
		E_USER_ERROR=>'E_USER_ERROR',
		E_USER_WARNING=>'E_USER_WARNING',
		E_USER_NOTICE=>'E_USER_NOTICE',
		E_STRICT=>'E_STRICT',
		E_RECOVERABLE_ERROR=>'E_RECOVERABLE_ERROR',
		E_DEPRECATED=>'E_DEPRECATED',
		E_USER_DEPRECATED=>'E_USER_DEPRECATED',
	 	E_ALL=>'E_ALL'
	];

/**
 * Error tracing
 * @param integer $n     contains the level of the error raised
 * @param string $str    contains the error message
 * @param string $file   contains the filename that the error was raised in
 * @param integer $line  contains the file line that the error was raised in
 */
function traceError($n, $str, $file, $line) {
	$backtrace = debug_backtrace(false);
	array_shift($backtrace);array_shift($backtrace);array_shift($backtrace);
	$textTraceArray = formatTraceAsText($backtrace);
	$data =	'MESSAGE: '.$str."\n".
		'FILE: '.$file.':'.$line."\n".
		'------------------------------------------------------------------------------------------------'."\n".
		implode('<br/>',$textTraceArray)."\n".
		'------------------------------------------------------------------------------------------------';
	sys::trace(LOG_ERR, T_ERROR, $str, $data, LABELS[$n]);
	sys::log(sprintf('%s - FILE: %s:%s', $str, $file, $line), LOG_ERR, 'kernel');
}


/**
 * Exception tracing
 * @param \Throwable $Ex
 */
function traceException(\Throwable $Ex) {
	$info = (is_callable([$Ex, 'getInfo'])) ? $Ex->getInfo() : null;
	$textTraceArray = formatTraceAsText($Ex->getTrace());
	$msg = '[CODE '.$Ex->getCode().'] '.$Ex->getMessage();
	$data =	'MESSAGE: '.$Ex->getMessage()."\n".
		'FILE: '.$Ex->getFile().':'.$Ex->getLine()."\n".
		$info."\n".
		'------------------------------------------------------------------------------------------------'."\n".
		implode("\n",$textTraceArray)."\n".
		'------------------------------------------------------------------------------------------------';
	sys::trace(LOG_ERR, T_ERROR, $msg, $data, get_class($Ex));
	sys::log(sprintf('%s[%s]: %s - %s - FILE: %s:%s', get_class($Ex), $Ex->getCode(), $Ex->getMessage(), $info, $Ex->getFile(), $Ex->getLine()), LOG_ERR, 'kernel');
}

function formatTraceAsText(array $traceArray) {
	$i = 0; $textTraceArray = [];
	foreach($traceArray as $tr) {
		if(!isset($tr['file'])) $tr['file'] = '';
		if(!isset($tr['line'])) $tr['line'] = '';
		if(!isset($tr['class'])) $tr['class'] = '';
		if(!isset($tr['type'])) $tr['type'] = '';
		$txt = '#'.$i.' '.$tr['file'].':'.$tr['line'].' -- '.$tr['class'].$tr['type'].$tr['function'].'(';
		if(!empty($tr['args'])) {
			foreach($tr['args'] as $arg) {
				switch($arg) {
					case (is_bool($arg)):
						$txt .= ($arg) ? ' true,' : ' false,';
						break;
					case (is_numeric($arg)):
						$txt .= " $arg,";
						break;
					case (is_string($arg)):
						$txt .= " '$arg',";
						break;
					case (is_array($arg)):
						$txt .= ' [Array],';
						break;
					case (is_object($arg)):
						$txt .= ' [Object],';
						break;
					default:
						$txt .= ' [mixed],';
				}
			}
			$txt = substr($txt,0,-1).' ';
		}
		$txt .= ')';
		$textTraceArray[] = $txt;
		++$i;
	}
	return $textTraceArray;
}
