<?php
namespace renovant\core;
use const renovant\core\trace\T_INFO;
use renovant\core\authz\ObjAuthz,
	renovant\core\authz\ObjAuthzInterface,
	renovant\core\authz\ObjTagsParser,
	renovant\core\container\Container,
	renovant\core\db\orm\Repository;
class CoreProxy {

	/** Object OID */
	protected string $_;
	/** Proxy-ed Object instance */
	protected ?object $Obj = null;
	/** AUTHZ verifier  */
	protected ?ObjAuthz $ObjAuthz;

	function __construct(string $id) {
		$this->_ = $id;
	}

	function __sleep() {
		return ['_'];
	}

	/** @throws \Exception */
	function __call(string $method, mixed $args): mixed {
		pcntl_signal_dispatch();
		$prevTraceFn = sys::traceFn($this->_.'->'.$method);
		try {
			// Obj & AUTHZ initialize
			if(!$this->Obj) {
				sys::context()->init(substr($this->_, 0, strrpos($this->_, '.')));
				$this->Obj = sys::cache(SYS_CACHE)->get($this->_) ?: sys::context()->container()->get($this->_, null, Container::FAILURE_SILENT);
				if($this->Obj instanceof ObjAuthzInterface) {
					if(!$ObjAuthz = sys::cache(SYS_CACHE)->get($this->_.':authz')) {
						sys::cache(SYS_CACHE)->set($this->_.':authz', $ObjAuthz = ObjTagsParser::parse($this->Obj));
					}
					$this->ObjAuthz = $ObjAuthz;
				}
			}
			// AUTHZ check
			if($this->Obj instanceof ObjAuthzInterface) $this->ObjAuthz->check($method, $args);

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
