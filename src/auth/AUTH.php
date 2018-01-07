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
	use \metadigit\core\CoreTrait;

	/** enable/disable JWT module
	 * @var boolean */
	protected $enableJWT = false;
	/** enable/disable SESSION module
	 * @var boolean */
	protected $enableSESSION = false;

	/** User custom data
	 * @var array */
	protected $data = [];
	/** Group ID
	 * @var integer|null */
	protected $GID = null;
	/** Group name
	 * @var string|null */
	protected $GROUP = null;
	/** User name (full-name)
	 * @var string|null */
	protected $NAME = null;
	/** User ID
	 * @var integer|null */
	protected $UID = null;

	function __sleep() {
		return ['_', 'enableJWT', 'enableSESSION'];
	}

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

	/**
	 * Get User data, whole set or single key
	 * @param string|null $key
	 * @return array|mixed|null
	 */
	function get($key=null) {
		return (is_null($key)) ? $this->data : ($this->data[$key] ?? null);
	}

	/**
	 * Get group ID
	 * @return integer|null
	 */
	function GID() {
		return $this->GID;
	}

	/**
	 * Get group name
	 * @return string|null
	 */
	function GROUP() {
		return $this->GROUP;
	}

	/**
	 * Get User name
	 * @return string|null
	 */
	function NAME() {
		return $this->NAME;
	}

	/**
	 * Set User data, also special values GID, GROUP, NAME, UID
	 * @param string $key
	 * @param mixed $value
	 * @return AUTH
	 */
	function set($key, $value) {
		switch ($key) {
			case 'GID': $this->GID = (integer) $value; break;
			case 'GROUP': $this->GROUP = (string) $value; break;
			case 'NAME': $this->NAME = (string) $value; break;
			case 'UID': $this->UID = (integer) $value; break;
			default: $this->data[$key] = $value;
		}
		return $this;
	}

	/**
	 * Get User ID
	 * @return integer|null
	 */
	function UID() {
		return $this->UID;
	}
}
