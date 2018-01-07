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

	const MODULES = [
		'COOKIE',
		'JWT',
		'SESSION'
	];

	/** User custom data
	 * @var array */
	protected $_data = [];
	/** Group ID
	 * @var integer|null */
	protected $_GID = null;
	/** Group name
	 * @var string|null */
	protected $_GROUP = null;
	/** User name (full-name)
	 * @var string|null */
	protected $_NAME = null;
	/** User ID
	 * @var integer|null */
	protected $_UID = null;

	/** active module
	 * @var string */
	protected $module = 'SESSION';

	/**
	 * AUTH constructor.
	 * @param string $module
	 * @throws AuthException
	 */
	function __construct($module='SESSION') {
		if(!in_array($module, self::MODULES)) throw new AuthException(1, [$module, implode(', ', self::MODULES)]);
		$this->module = $module;
	}

	function __sleep() {
		return ['_', 'module'];
	}

	/**
	 * @throws AuthException
	 */
	function init() {
		sys::trace(LOG_DEBUG, T_INFO, 'initialize module '.$this->module, null, $this->_.'->init');
		switch ($this->module) {
			case 'COOKIE':
				// @TODO COOKIE module
				break;
			case 'JWT':
				// @TODO JWT module
				break;
			case 'SESSION':
				if(session_status() != PHP_SESSION_ACTIVE) throw new AuthException(13);
				if($_SESSION['__AUTH__']) foreach ($_SESSION['__AUTH__'] as $k => $v)
					$this->set($k, $v);
		}
	}

	function commit() {
		sys::trace(LOG_DEBUG, T_INFO, null, null, $this->_.'->commit');
		switch ($this->module) {
			case 'COOKIE':
				// @TODO COOKIE module
				break;
			case 'JWT':
				// @TODO JWT module
				break;
			case 'SESSION':
				$_SESSION['__AUTH__'] = array_merge([
					'GID'	=> $this->_GID,
					'GROUP'	=> $this->_GROUP,
					'NAME'	=> $this->_NAME,
					'UID'	=> $this->_UID
				], $this->_data);
		}
	}

	/**
	 * Get User data, whole set or single key
	 * @param string|null $key
	 * @return array|mixed|null
	 */
	function get($key=null) {
		return (is_null($key)) ? $this->_data : ($this->_data[$key] ?? null);
	}

	/**
	 * Get group ID
	 * @return integer|null
	 */
	function GID() {
		return $this->_GID;
	}

	/**
	 * Get group name
	 * @return string|null
	 */
	function GROUP() {
		return $this->_GROUP;
	}

	/**
	 * Get User name
	 * @return string|null
	 */
	function NAME() {
		return $this->_NAME;
	}

	/**
	 * Set User data, also special values GID, GROUP, NAME, UID
	 * @param string $key
	 * @param mixed $value
	 * @return AUTH
	 */
	function set($key, $value) {
		switch ($key) {
			case 'GID': $this->_GID = (integer) $value; break;
			case 'GROUP': $this->_GROUP = (string) $value; break;
			case 'NAME': $this->_NAME = (string) $value; break;
			case 'UID': $this->_UID = (integer) $value; break;
			default: $this->_data[$key] = $value;
		}
		return $this;
	}

	/**
	 * Get User ID
	 * @return integer|null
	 */
	function UID() {
		return $this->_UID;
	}
}
