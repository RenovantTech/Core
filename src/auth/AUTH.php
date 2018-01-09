<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\auth;
use const metadigit\core\DATA_DIR;
use const metadigit\core\trace\{T_ERROR, T_INFO};
use metadigit\core\sys,
	metadigit\core\http\Event as HttpEvent,
	Firebase\JWT\BeforeValidException,
	Firebase\JWT\ExpiredException,
	Firebase\JWT\JWT;
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
	const JWT_KEY = DATA_DIR.'jwt.key';

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
	/** XSRF-TOKEN value
	 * @var string|null */
	protected $_XSRF_TOKEN = null;

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
	 * @throws Exception
	 */
	function __construct($module='SESSION') {
		if(!in_array($module, self::MODULES)) throw new Exception(1, [$module, implode(', ', self::MODULES)]);
		$this->module = $module;
		switch ($this->module) {
			case 'JWT':
				if(!class_exists('Firebase\JWT\JWT')) throw new Exception(12);
				if(!file_exists(self::JWT_KEY))
					file_put_contents(self::JWT_KEY, base64_encode(openssl_random_pseudo_bytes(64)));
				break;
		}
	}

	function __sleep() {
		return ['_', 'module', 'skipAuthApps', 'skipAuthUrls', 'skipXSRFApps', 'skipXSRFUrls'];
	}

	/**
	 * Initialize AUTH module, perform Authentication & Security checks
	 * To be invoked via event listener before HTTP Controller execution (HTTP:ROUTE or HTTP:CONTROLLER).
	 * @param HttpEvent $Event
	 * @throws AuthException
	 * @throws Exception
	 */
	function init(HttpEvent $Event) {
		$prevTraceFn = sys::traceFn($this->_.'->init');
		try {
			sys::trace(LOG_DEBUG, T_INFO, 'initialize '.$this->module.' module');
			switch ($this->module) {
				case 'COOKIE':
					// @TODO COOKIE module
					break;
				case 'JWT':
					if(isset($_COOKIE['JWT'])) {
						try {
							$token = (array) JWT::decode($_COOKIE['JWT'], file_get_contents(self::JWT_KEY), ['HS512']);
							$this->_XSRF_TOKEN = $token['XSRF-TOKEN'] ?? null;
							if(!empty($token['data'])) {
								foreach ($token['data'] as $k => $v)
									$this->set($k, $v);
								sys::trace(LOG_DEBUG, T_INFO, 'JWT AUTH OK', $token['data']);
							}
						} catch (BeforeValidException $Ex) {
							// skip, go on
						} catch (ExpiredException $Ex) {
							// skip, go on
						} catch (\Exception $Ex) { // include SignatureInvalidException, UnexpectedValueException
							sys::trace(LOG_ERR, T_ERROR, 'JWT invalid: BLOCK ACCESS');
							throw new AuthException(21);
						}
					}
					break;
				case 'SESSION':
					if(session_status() != PHP_SESSION_ACTIVE) throw new Exception(23);
					if(isset($_SESSION['__AUTH__'])) {
						$this->_XSRF_TOKEN = $_SESSION['XSRF-TOKEN'] ?? null;
						if(!empty($_SESSION['__AUTH__'])) {
							foreach ($_SESSION['__AUTH__'] as $k => $v)
								$this->set($k, $v);
							sys::trace(LOG_DEBUG, T_INFO, 'SESSION AUTH OK', $_SESSION['__AUTH__']);
						}
					}
					break;
			}
			// initialize XSRF-TOKEN COOKIE
			if(!isset($_COOKIE['XSRF-TOKEN'])) {
				sys::trace(LOG_DEBUG, T_INFO, 'initialize XSRF-TOKEN');
				$token = md5(uniqid(rand(1,999)));
				setcookie('XSRF-TOKEN', $token, 0, '/', null, false, false);
				$this->_XSRF_TOKEN = $token;
			}
			// verify required AUTH & XSRF-TOKEN
			$Req = $Event->getRequest();
			$APP = $Req->getAttribute('APP');
			$URI = $Req->URI();
			$XSRFToken = $Req->getHeader('X-XSRF-TOKEN');

			if(!$this->_UID && $URI != '/' && !in_array($APP, $this->skipAuthApps))
				$this->checkAUTH($URI);

			if($XSRFToken && $XSRFToken == $this->_XSRF_TOKEN)
				sys::trace(LOG_DEBUG, T_INFO, 'XSRF-TOKEN OK');
			elseif ($XSRFToken && $XSRFToken != $this->_XSRF_TOKEN)
				throw new AuthException(50);
			elseif($URI != '/' && !in_array($APP, $this->skipXSRFApps))
				$this->checkXSRF($URI);
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	/**
	 * @param $URI
	 * @throws AuthException
	 */
	protected function checkAUTH($URI) {
		foreach ($this->skipAuthUrls as $url)
			if(preg_match($url, $URI)) return;
		throw new AuthException(101);
	}

	/**
	 * @param $URI
	 * @throws AuthException
	 */
	protected function checkXSRF($URI) {
		foreach ($this->skipXSRFUrls as $url)
			if(preg_match($url, $URI)) return;
		throw new AuthException(102);
	}

	/**
	 * Commit AUTH data to module storage.
	 * To be invoked via event listener after HTTP Controller execution (HTTP:VIEW & HTTP:EXCEPTION).
	 */
	function commit() {
		sys::trace(LOG_DEBUG, T_INFO, null, null, $this->_.'->commit');
		$data = array_merge([
			'GID'	=> $this->_GID,
			'GROUP'	=> $this->_GROUP,
			'NAME'	=> $this->_NAME,
			'UID'	=> $this->_UID
		], $this->_data);
		switch ($this->module) {
			case 'COOKIE':
				// @TODO COOKIE module
				break;
			case 'JWT':
				$token = [
					//'aud' => 'http://example.com',
					'exp' => time()+3600,
					'iat' => time()-1,
					//'iss' => 'http://example.org',
					'nbf' => time()-1,
					'data' => $this->_UID ? $data : null,
					'XSRF-TOKEN'=>$this->_XSRF_TOKEN
				];
				setcookie('JWT', JWT::encode($token, file_get_contents(self::JWT_KEY), 'HS512'), 0, '/', '', true, true);
				break;
			case 'SESSION':
				$_SESSION['__AUTH__'] = $this->_UID ? $data : null;
				$_SESSION['XSRF-TOKEN'] = $this->_XSRF_TOKEN;
		}
	}

	/**
	 * Erase AUTH data.
	 * To be invoked on LOGOUT or other required situations.
	 */
	function erase() {
		sys::trace(LOG_DEBUG, T_INFO, null, null, $this->_.'->erase');
		$this->_data = [];
		$this->_GID = $this->_GROUP = $this->_NAME = $this->_UID = null;
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
