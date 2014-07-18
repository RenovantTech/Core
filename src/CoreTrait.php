<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core;
/**
 * Basic trait used by almost all framework classes.
 * It implements:
 * - object ID (unique Object Identifier);
 * - trace support..
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
trait CoreTrait {

	/** OID (Object Identifier)
	 * @var string */
	protected $_oid;

	function _oid() {
		return is_null($this->_oid) ? __CLASS__ : $this->_oid;
	}
	/**
	 * Object instance trace
	 * @param integer $level trace level, use a LOG_? constant value
	 * @param integer $type trace type, use a TRACE_? constant value
	 * @param string $function the calling object method
	 * @param string $msg the trace message
	 * @param mixed $data the trace data
	 * @return void
	 */
	protected function trace($level=LOG_DEBUG, $type=TRACE_DEFAULT, $function, $msg=null, $data=null) {
		$method = ((is_null($this->_oid)) ? __CLASS__ : $this->_oid).'->'.$function;
		Kernel::trace($level, $type, $method, $msg, $data);
	}

	protected function _namespace() {
		return (is_null($this->_oid)) ? 'global' : substr($this->_oid, 0, strrpos($this->_oid,'.'));
	}
}