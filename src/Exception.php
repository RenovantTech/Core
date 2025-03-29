<?php
namespace renovant\core;

use renovant\core\trace\Tracer;

use const renovant\core\trace\T_INFO;

class Exception extends \Exception {
	public const LEVEL = E_USER_ERROR;

	protected $data;

	/**
	 * @param int $code
	 * @param string|array $message
	 * @param mixed $data
	 */
	final public function __construct($code = 0, $message = null, $data = null) {
		$this->data = $data;
		if (defined(get_class($this) . "::COD$code")) {
			$tpl = constant(get_class($this) . "::COD$code");
			if (is_array($message)) {
				array_unshift($message, $tpl);
				$message = call_user_func_array('sprintf', $message);
			} elseif (is_null($message)) {
				$message = $tpl;
			}
		}
		parent::__construct((string)$message, (int)$code);
		$class = sysinfo::class(get_class($this));
		sys::trace(LOG_DEBUG, T_INFO, '[' . $class . ':' . $this->getCode() . '] ' . $this->getMessage());
	}

	/**
	 * @return mixed|null
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * can be overridden by subclass to provide extended debug information
	 */
	public function getInfo() {
	}

	public function trace() {
		Tracer::onException($this);
	}
}
