<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core;
use const metadigit\core\trace\T_INFO;
use metadigit\core\context\Context,
	metadigit\core\container\Container,
	metadigit\core\container\ContainerException;
/**
 * Proxy for injected objects.
 * @author Daniele Sciacchitano <dan@metadigit.it>
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
		$prevTraceFn = sys::traceFn($this->_.'->'.$method);
		try {
			pcntl_signal_dispatch();
			if(!$this->Obj) {
				sys::context()->init(substr($this->_, 0, strrpos($this->_, '.')));
				$this->Obj = sys::cache('sys')->get($this->_) ?: sys::context()->container()->get($this->_, null, Container::FAILURE_SILENT);
			}
			ACL_OBJECTS and !defined(get_class($this->Obj).'::ACL_SKIP') and sys::acl()->onObject($this->_, $method, defined('SESSION_UID')? SESSION_UID : null);
			sys::trace(LOG_DEBUG, T_INFO);
			return call_user_func_array([$this->Obj, $method], $args);
		} finally {
			sys::traceFn($prevTraceFn);
			pcntl_signal_dispatch();
		}
	}
}
