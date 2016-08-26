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

	/** parent Container/Context OID
	 * @var string */
	protected $_container;
	/** Object OID
	 * @var string */
	protected $_oid;
	/** Proxy-ed Object instance
	 * @var Object */
	protected $_Obj = null;

	/**
	 * @param string $id proxied object OID
	 * @param string $container
	 */
	function __construct($id, $container=null) {
		$this->_oid = $id;
		$this->_container = $container;
	}

	function __sleep() {
		return ['_oid', '_container'];
	}

	function __call($method, $args) {
		$prevTraceFn = Kernel::traceFn();
		try {
			if(is_null($this->_Obj)) $this->_Obj = Kernel::cache('kernel')->get($this->_container)->get($this->_oid, null, Container::FAILURE_SILENT);
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
