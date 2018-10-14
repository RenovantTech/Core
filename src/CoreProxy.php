<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core;
use const renovant\core\trace\T_INFO;
use renovant\core\container\Container,
	renovant\core\container\ContainerException;
/**
 * Proxy for injected objects.
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
class CoreProxy {

	/** Object OID
	 * @var string */
	protected $_;
	/** Proxy-ed Object instance
	 * @var Object */
	protected $Obj = null;

	/**
	 * @param string $id proxy-ed object OID
	 */
	function __construct($id) {
		$this->_ = $id;
	}

	function __sleep() {
		return ['_'];
	}

	/**
	 * @param $method
	 * @param $args
	 * @return mixed
	 * @throws ContainerException
	 * @throws \Exception
	 */
	function __call($method, $args) {
		pcntl_signal_dispatch();
		$prevTraceFn = sys::traceFn($this->_.'->'.$method);
		try {
			if(!$this->Obj) {
				sys::context()->init(substr($this->_, 0, strrpos($this->_, '.')));
				$this->Obj = sys::cache('sys')->get($this->_) ?: sys::context()->container()->get($this->_, null, Container::FAILURE_SILENT);
			}
			defined('SYS_ACL_SERVICES') and !defined(get_class($this->Obj).'::ACL_SKIP') and sys::acl()->onObject($this->_, $method, sys::auth()->UID());
			sys::trace(LOG_DEBUG, T_INFO);
			return call_user_func_array([$this->Obj, $method], $args);
		} finally {
			sys::traceFn($prevTraceFn);
			pcntl_signal_dispatch();
		}
	}

	static function __set_state($data) {
		return new CoreProxy($data['id']);
	}
}
