<?php
namespace renovant\core;

use renovant\core\authz\{ObjAuthz, ObjAuthzInterface, ObjTagsParser};
use renovant\core\container\Container;
use renovant\core\db\orm\Repository;

use const renovant\core\trace\T_INFO;

class CoreProxy {
	/** Object OID */
	protected string $_;
	/** Proxy-ed Object instance */
	protected ?object $Obj = null;
	/** AUTHZ verifier  */
	protected ?ObjAuthz $ObjAuthz;

	public function __construct(string $id) {
		$this->_ = $id;
	}

	public function __sleep() {
		return ['_'];
	}

	/** @throws \Exception */
	public function __call(string $method, mixed $args): mixed {
		pcntl_signal_dispatch();
		$prevTraceFn = sys::traceFn($this->_ . '->' . $method);
		try {
			// Obj & AUTHZ initialize
			if (!$this->Obj) {
				sys::context()->init(substr($this->_, 0, strrpos($this->_, '.')));
				$this->Obj = sys::cache(SYS_CACHE)->get($this->_) ?: sys::context()->container()->get($this->_, null, Container::FAILURE_SILENT);
				if ($this->Obj instanceof ObjAuthzInterface) {
					if (!$ObjAuthz = sys::cache(SYS_CACHE)->get($this->_ . ObjAuthz::CACHE_SUFFIX)) {
						sys::cache(SYS_CACHE)->set($this->_ . ObjAuthz::CACHE_SUFFIX, $ObjAuthz = ObjTagsParser::parse($this->Obj), 0, 'authz');
					}
					$this->ObjAuthz = $ObjAuthz;
				}
			}
			// AUTHZ check
			if ($this->Obj instanceof ObjAuthzInterface) {
				$this->ObjAuthz->check($method, $args);
			}

			if ($this->Obj instanceof Repository) {
				sys::trace(LOG_DEBUG, T_INFO, $this->_ . '->' . $method, null, $prevTraceFn);
			} else {
				sys::trace();
			}
			return call_user_func_array([$this->Obj, $method], $args);
		} finally {
			sys::traceFn($prevTraceFn);
			pcntl_signal_dispatch();
		}
	}

	public static function __set_state($data) {
		return new CoreProxy($data['id']);
	}
}
