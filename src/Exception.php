<?php
namespace renovant\core;
use const renovant\core\trace\T_INFO;
use renovant\core\trace\Tracer;
class Exception extends \Exception {

	const LEVEL = E_USER_ERROR;

	protected $data;

	/**
	 * @param int $code
	 * @param string|array $message
	 * @param mixed $data
	 */
	final function __construct($code=0, $message=null, $data=null) {
		$this->data = $data;
		if($tpl = @constant(get_class($this)."::COD$code")) {
			if(is_array($message)) {
				array_unshift($message, $tpl);
				$message = call_user_func_array('sprintf', $message);
			} elseif(is_null($message)) {
				$message = $tpl;
			}
		}
		parent::__construct((string)$message, (int)$code);
		$class = sys::info(get_class($this), sys::INFO_CLASS);
		sys::trace(LOG_DEBUG, T_INFO, '['.$class.':'.$this->getCode().'] '.$this->getMessage());
	}

	/**
	 * @return mixed|null
	 */
	function getData() {
		return $this->data;
	}

	/**
	 * can be overridden by subclass to provide extended debug information
	 */
	function getInfo() {
	}

	function trace() {
		Tracer::onException($this);
	}
}
