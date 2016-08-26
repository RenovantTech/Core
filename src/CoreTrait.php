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

	protected function _namespace() {
		return (is_null($this->_oid)) ? 'global' : substr($this->_oid, 0, strrpos($this->_oid,'.'));
	}
}
