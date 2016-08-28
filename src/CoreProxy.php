<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core;
use metadigit\core\depinjection\Container,
	metadigit\core\depinjection\ContainerException;
/**
 * Proxy for injected objects.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class CoreProxy {

	/** Object OID
	 * @var string */
	protected $_oid;
	/** Proxy-ed Object instance
	 * @var Object */
	protected $_Obj = null;

	/**
	 * @param string $id proxied object OID
	 */
	function __construct($id) {
		$this->_oid = $id;
	}

	function __sleep() {
		return ['_oid'];
	}

	function __call($method, $args) {
		$prevTraceFn = Kernel::traceFn();
		try {
			if(is_null($this->_Obj)) {
				$namespace = substr($this->_oid, 0, strrpos($this->_oid, '.'));
				$this->_Obj = Kernel::cache('kernel')->get($namespace.'.Container')->get($this->_oid, null, Container::FAILURE_SILENT);
			}
			if(is_object($this->_Obj)) {
				Kernel::traceFn($this->_oid.'->'.$method);
				TRACE and Kernel::trace(LOG_DEBUG, TRACE_DEFAULT);
				$r = call_user_func_array([$this->_Obj, $method], $args);
				Kernel::traceFn($prevTraceFn);
				return $r;
			}
			throw new ContainerException(4, [$this->_oid]);
		} catch (\Exception $Ex) {
			Kernel::traceFn($prevTraceFn);
			throw $Ex;
		}
	}
}
