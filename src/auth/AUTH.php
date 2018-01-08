<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\auth;
use metadigit\core\http\Request;
use const metadigit\core\trace\{T_ERROR, T_INFO};
use metadigit\core\sys,
	metadigit\core\http\Event as HttpEvent;
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

	/** APPs to be skipped by checkAUTH()
	 * @var array */
	protected $skipAuthApps = [];
	/** URLs to be skipped by checkAUTH()
	 * @var array */
	protected $skipAuthUrls = [];
	/** APPs to be skipped by checkXSRF()
	 * @var array */
	protected $skipXSRFApps = [];
	/** URLs to be skipped by checkXSRF()
	 * @var array */
	protected $skipXSRFUrls = [];

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
		return ['_', 'module', 'skipAuthApps', 'skipAuthUrls', 'skipXSRFApps', 'skipXSRFUrls'];
	}

	/**
	 * Initialize AUTH module, activating required resources.
	 * To be invoked via event listener before HTTP Controller execution (HTTP:ROUTE or HTTP:CONTROLLER).
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
				if(isset($_SESSION['__AUTH__'])) foreach ($_SESSION['__AUTH__'] as $k => $v)
					$this->set($k, $v);
		}
	}

	/**
	 * Authentication & Security check (with XSRF protection).
	 * To be invoked via event listener before HTTP Controller execution (HTTP:ROUTE or HTTP:CONTROLLER).
	 * @param HttpEvent $Event
	 */
	function check(HttpEvent $Event) {
		$Req = $Event->getRequest();
		$APP = $Req->getAttribute('APP');
		$URI = $Req->URI();
		if(!$this->_UID && $URI != '/' && !in_array($APP, $this->skipAuthApps)) $this->checkAUTH($Event, $Req);
		if($URI != '/' && !in_array($APP, $this->skipXSRFApps)) $this->checkXSRF($Event, $Req);
	}

	protected function checkAUTH(HttpEvent $Event, Request $Req) {
		$URI = $Req->URI();
		foreach ($this->skipAuthUrls as $url)
			if(preg_match($url, $URI)) return;
		sys::trace(LOG_ERR, T_ERROR, 'AUTH not valid: BLOCK ACCESS', null, $this->_.'->check');
		http_response_code(401);
		$Event->stopPropagation();
	}

	protected function checkXSRF(HttpEvent $Event, Request $Req) {
		$URI = $Req->URI();
		foreach ($this->skipXSRFUrls as $url)
			if(preg_match($url, $URI)) return;
		if(isset($_SESSION['XSRF-TOKEN'])) {
			$token = $Req->getHeader('X-XSRF-TOKEN');
			if($token != $_SESSION['XSRF-TOKEN']) {
				sys::trace(LOG_ERR, T_ERROR, 'XSRF-TOKEN not valid: BLOCK ACCESS', null, $this->_.'->check');
				http_response_code(401);
				$Event->stopPropagation();
			}
		} else {
			$token = md5(uniqid(rand(1,999)));
			sys::trace(LOG_DEBUG, T_INFO, 'initialize XSRF-TOKEN: '.$token, null, $this->_.'->check');
			setcookie('XSRF-TOKEN', $token, 0, '/');
			$_SESSION['XSRF-TOKEN'] = $token;
		}
	}

	/**
	 * Commit AUTH data to module storage.
	 * To be invoked via event listener after HTTP Controller execution (HTTP:VIEW & HTTP:EXCEPTION).
	 */
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
	 * Erase AUTH data.
	 * To be invoked on LOGOUT or other required situations.
	 */
	function erase() {
		sys::trace(LOG_DEBUG, T_INFO, null, null, $this->_.'->erase');
		switch ($this->module) {
			case 'COOKIE':
				// @TODO COOKIE module
				break;
			case 'JWT':
				// @TODO JWT module
				break;
			case 'SESSION':
				$token = $_SESSION['XSRF-TOKEN'];
				session_regenerate_id(true);
				session_unset();
				$_SESSION['XSRF-TOKEN'] = $token;
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
