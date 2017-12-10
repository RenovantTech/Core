<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core;
use metadigit\core\context\Context;
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
	protected $_;

	function _() {
		return is_null($this->_) ? __CLASS__ : $this->_;
	}

	/**
	 * @return Context
	 */
	protected function context() {
		return Context::factory(substr($this->_, 0, strrpos($this->_,'.')));
	}
}
