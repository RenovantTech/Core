<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\auth;
use const metadigit\core\trace\T_INFO;
use metadigit\core\sys;
/**
 * Authentication Manager.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class AUTH {

	/** enable/disable JWT module
	 * @var boolean */
	protected $enableJWT = false;
	/** enable/disable SESSION module
	 * @var boolean */
	protected $enableSESSION = false;

	function __construct(bool $enableJWT=false, bool $enableSESSION=false) {
		$this->enableJWT = $enableJWT;
		$this->enableSESSION = $enableSESSION;
	}

	function init() {
		sys::trace(LOG_DEBUG, T_INFO, 'initialize AUTH module', null, 'sys.AUTH->init');
		if($this->enableJWT) {
			// @TODO initialize JWT module
		}
		if($this->enableSESSION) {
			// @TODO initialize SESSION module
		}
		return $this;
	}
}
