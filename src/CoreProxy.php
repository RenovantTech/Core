<?php
namespace renovant\core;
use renovant\core\db\orm\Repository;
use const renovant\core\trace\T_INFO;
use renovant\core\container\Container,
	renovant\core\container\ContainerException;
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
				$this->Obj = sys::cache(SYS_CACHE)->get($this->_) ?: sys::context()->container()->get($this->_, null, Container::FAILURE_SILENT);
			}
			// ACL check
			method_exists($this->Obj, '_acl') and ($this->Obj)->_acl($method);
			if($this->Obj instanceof Repository) sys::trace(LOG_DEBUG, T_INFO, $this->_.'->'.$method, null, $prevTraceFn);
			else sys::trace();
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
