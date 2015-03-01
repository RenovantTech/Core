<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\depinjection;
use metadigit\core\Kernel;
/**
 * Proxy for injected objects.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class ObjectProxy {

	/** parent Container/Context OID
	 * @var string */
	protected $_container;
	/** Object OID
	 * @var string */
	protected $_oid;
	/** Proxed Object instance
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
		if(is_null($this->_Obj)) $this->_Obj = Kernel::getCache()->get($this->_container)->get($this->_oid, null, Container::FAILURE_SILENT);
		if(is_object($this->_Obj)) return call_user_func_array([$this->_Obj, $method], $args);
		throw new ContainerException(4, [$this->_oid]);
	}
}
