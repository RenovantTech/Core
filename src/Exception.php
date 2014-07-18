<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core;
/**
 * Base Exception class
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Exception extends \Exception {

	const LEVEL = E_USER_ERROR;

	protected $args;

	final function __construct($msg, $code=0) {
		$this->args = func_get_args();
		if(is_int($msg)) {
			$code = $msg;
			$msg = constant(get_class($this)."::COD$code");
			$n_args = func_num_args()-1;
			// iterate on arguments, setting values in message
			//@FIXME use sprintf http://www.php.net/manual/en/function.sprintf.php
			if($n_args>=1) {
				for($i=1; $i<=$n_args; $i++) {
					$val = func_get_arg($i);
					if(is_array($val)) {
						foreach($val as $k=>$v) {
							$msg = str_replace('{'.$i.$k.'}',$v,$msg);
						}
					} else $msg = str_replace('{'.$i.'}',$val,$msg);
				}
			}
		}
		parent::__construct((string)$msg, (int)$code);
		TRACE and Kernel::trace(TRACE_DEFAULT, 1, get_class($this), '[CODE '.$this->getCode().'] '.$this->getMessage());
	}

	/**
	 * can be overridden by subclass to provide extended debug information
	 */
	function getInfo() {
	}

	function trace() {
		KernelDebugger::traceException($this);
	}
}