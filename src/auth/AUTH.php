<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\auth;
use const metadigit\core\DATA_DIR;
use const metadigit\core\trace\T_INFO;
use metadigit\core\sys,
	metadigit\core\auth\provider\PdoProvider,
	metadigit\core\auth\provider\ProviderInterface,
	metadigit\core\http\CryptoCookie,
	metadigit\core\http\Exception as HttpException,
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

	const COOKIE_AUTH		= 'AUTH-TOKEN';
	const COOKIE_REFRESH	= 'REFRESH-TOKEN';
	const COOKIE_REMEMBER	= 'REMEMBER-TOKEN';
	const COOKIE_XSRF		= 'XSRF-TOKEN';
	const HEADER_XSRF		= 'X-XSRF-TOKEN';
	const LOGIN_UNKNOWN			= -1;
	const LOGIN_DISABLED		= -2;
	const LOGIN_PWD_MISMATCH	= -3;
	const LOGIN_EXCEPTION		= -4;
	const MODULES = [
		'COOKIE',
		'JWT',
		'SESSION'
	];
	const JWT_KEY = DATA_DIR.'JWT.key';
	const TTL_ACCESS	= 300;
	const TTL_REFRESH	= 86400;
	const TTL_REMEMBER	= 2592000;

	/** Pending commit  flag
	 * @var bool */
	protected $_commit = false;
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

	/** Auth Token TTL
	 * @var int */
	protected $authTTL = self::TTL_ACCESS;
	/** Active module
	 * @var string */
	protected $module = 'SESSION';
	/** Refresh Token TTL
	 * @var int */
	protected $refreshTTL = self::TTL_REFRESH;
	/** Remember Token TTL
	 * @var int */
	protected $rememberTTL = self::TTL_REMEMBER;

	/** REMEMBER-TOKEN flag
	 * @var boolean */
	protected $setRememberToken = false;
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
					file_put_contents(self::JWT_KEY, base64_encode(random_bytes(64)));
				break;
		}
	}

	function __sleep() {
		return ['_', 'authTTL', 'module', 'refreshTTL', 'rememberTTL', 'skipAuthApps', 'skipAuthUrls', 'skipXSRFApps', 'skipXSRFUrls'];
	}

	/**
	 * Initialize AUTH module, perform Authentication & Security checks
	 * To be invoked via event listener before HTTP Controller execution (HTTP:INIT, HTTP:ROUTE or HTTP:CONTROLLER).
	 * @param HttpEvent $Event
	 * @throws AuthException
	 * @throws Exception
	 */
	function init(HttpEvent $Event) {
		$prevTraceFn = sys::traceFn($this->_.'->init');
		try {
			$Req = $Event->getRequest();
			$APP = $Req->getAttribute('APP');
			$URI = $Req->URI();

			// AUTH-TOKEN & REFRESH-TOKEN
			switch ($this->module) {
				case 'COOKIE':
					// @TODO COOKIE module
					break;
				case 'JWT':
					if (isset($_COOKIE[self::COOKIE_AUTH])) {
						try {
							$token = (array)JWT::decode($_COOKIE[self::COOKIE_AUTH], file_get_contents(self::JWT_KEY), ['HS512']);
							if (isset($token['data']) && $token['data'] = (array)$token['data']) {
								foreach ($token['data'] as $k => $v)
									$this->set($k, $v);
								$this->_commit = false;
								sys::trace(LOG_DEBUG, T_INFO, 'JWT AUTH-TOKEN OK', $token['data']);
								break;
							}
						} catch (ExpiredException $Ex) {
							sys::trace(LOG_DEBUG, T_INFO, 'JWT AUTH-TOKEN exception: EXPIRED');
						} catch (BeforeValidException $Ex) {
							sys::trace(LOG_DEBUG, T_INFO, 'JWT AUTH-TOKEN exception: BEFORE-VALID');
						} catch (\Exception $Ex) { // include SignatureInvalidException, UnexpectedValueException
							sys::trace(LOG_DEBUG, T_INFO, 'JWT AUTH-TOKEN exception: INVALID');
						}
					}
					if (isset($_COOKIE[self::COOKIE_REFRESH])) {
						try {
							$refreshToken = (new CryptoCookie(self::COOKIE_REFRESH))->read();
							if($this->provider()->checkRefreshToken($refreshToken['UID'], $refreshToken['TOKEN'])) {
								$this->provider()->authenticateById($refreshToken['UID']);
								$this->_commit = true;
								sys::trace(LOG_DEBUG, T_INFO, 'JWT REFRESH-TOKEN OK');
								break;
							}
						} catch (HttpException $Ex) { // CryptoCookie Exception
							sys::trace(LOG_DEBUG, T_INFO, 'JWT REFRESH-TOKEN exception: INVALID');
						}
					}
					if (isset($_COOKIE[self::COOKIE_REMEMBER])) {
						try {
							$rememberToken = (new CryptoCookie(self::COOKIE_REMEMBER))->read();
							if($this->provider()->checkRememberToken($rememberToken['UID'], $rememberToken['TOKEN'])) {
								$this->provider()->authenticateById($rememberToken['UID']);
								$this->_commit = true;
								sys::trace(LOG_DEBUG, T_INFO, 'JWT REMEMBER-TOKEN OK');
								break;
							}
						} catch (HttpException $Ex) { // CryptoCookie Exception
							sys::trace(LOG_DEBUG, T_INFO, 'JWT REMEMBER-TOKEN exception: INVALID');
						}
					}
					break;
				case 'SESSION':
					if (session_status() != PHP_SESSION_ACTIVE) throw new Exception(23);
					if (isset($_SESSION['__AUTH__']) && is_array($_SESSION['__AUTH__'])) {
						foreach ($_SESSION['__AUTH__'] as $k => $v)
							$this->set($k, $v);
						$this->_commit = false;
						sys::trace(LOG_DEBUG, T_INFO, 'SESSION AUTH OK', $_SESSION['__AUTH__']);
					}
					break;
			}
			if (!$this->_UID && $URI != '/' && !in_array($APP, $this->skipAuthApps) && !$this->checkAUTH($URI))
				throw new AuthException(101, [$this->module]);

			// XSRF-TOKEN
			if(!isset($_COOKIE[self::COOKIE_XSRF]))
				$this->_commit = true;
			else
				$this->_XSRF_TOKEN = $_COOKIE[self::COOKIE_XSRF];
			$XSRFToken = $Req->getHeader(self::HEADER_XSRF);
			if ($XSRFToken && $XSRFToken === $this->_XSRF_TOKEN)
				sys::trace(LOG_DEBUG, T_INFO, 'XSRF-TOKEN OK');
			elseif ($XSRFToken && $XSRFToken != $this->_XSRF_TOKEN)
				throw new AuthException(50, [$this->module]);
			elseif ($URI != '/' && !in_array($APP, $this->skipXSRFApps) && !$this->checkXSRF($URI))
				throw new AuthException(102, [$this->module]);

		} catch (AuthException $Ex) {
			$this->_commit = true;
			throw $Ex;
		} finally {
			$this->commit(); // need on Exception to regenerate JWT/SESSION & XSRF-TOKEN
			sys::traceFn($prevTraceFn);
		}
	}

	/**
	 * @param $URI
	 * @return boolean
	 */
	protected function checkAUTH($URI) {
		foreach ($this->skipAuthUrls as $url)
			if(preg_match($url, $URI)) return true;
		return false;
	}

	/**
	 * @param $URI
	 * @return boolean
	 */
	protected function checkXSRF($URI) {
		foreach ($this->skipXSRFUrls as $url)
			if(preg_match($url, $URI)) return true;
		return false;
	}

	/**
	 * Commit AUTH data & XSRF-TOKEN to module storage.
	 * To be invoked via event listener after HTTP Controller execution (HTTP:VIEW & HTTP:EXCEPTION).
	 */
	function commit() {
		if(!$this->_commit) return;
		$prevTraceFn = sys::traceFn($this->_.'->commit');
		try {
			// XSRF-TOKEN (cookie + header)
			sys::trace(LOG_DEBUG, T_INFO, 'initialize XSRF-TOKEN');
			$this->_XSRF_TOKEN = substr(base64_encode(random_bytes(64)), 0, 64);
			setcookie(self::COOKIE_XSRF, $this->_XSRF_TOKEN, 0, '/', null, false, false);
			header(self::HEADER_XSRF.': '.$this->_XSRF_TOKEN);

			if (is_null($this->_UID)) return;

			// AUTH-TOKEN
			$data = array_merge([
				'GID' => $this->_GID,
				'GROUP' => $this->_GROUP,
				'NAME' => $this->_NAME,
				'UID' => $this->_UID
			], $this->_data);
			switch ($this->module) {
				case 'COOKIE':
					// @TODO COOKIE module
					break;
				case 'JWT':
					sys::trace(LOG_DEBUG, T_INFO, 'initialize JWT AUTH-TOKEN');
					$authToken = [
						//'aud' => 'http://example.com',
						'exp' => time() + $this->authTTL, // Expiry
						'iat' => time() - 1, // Issued At
						//'iss' => 'http://example.org', // Issuer
						'nbf' => time() - 1, // Not Before
						'data' => $data
					];
					setcookie(self::COOKIE_AUTH, JWT::encode($authToken, file_get_contents(self::JWT_KEY), 'HS512'), time() + $this->authTTL, '/', null, true, true);
					break;
				case 'SESSION':
					sys::trace(LOG_DEBUG, T_INFO, 'update SESSION data');
					$_SESSION['__AUTH__'] = $data;
			}

			// REFRESH-TOKEN
			sys::trace(LOG_DEBUG, T_INFO, 'initialize REFRESH-TOKEN');
			$refreshToken = [
				'UID'	=> $this->_UID,
				'TOKEN'	=> substr(base64_encode(random_bytes(64)), 0, 64)
			];
			$this->provider()->setRefreshToken($this->UID(), $refreshToken['TOKEN'], time()+$this->refreshTTL);
			(new CryptoCookie(self::COOKIE_REFRESH, 0, '/', null, false, true))->write($refreshToken);

			// REMEMBER-TOKEN
			if($this->setRememberToken) {
				sys::trace(LOG_DEBUG, T_INFO, 'initialize REMEMBER-TOKEN');
				$rememberToken = [
					'UID'	=> $this->_UID,
					'TOKEN'	=> substr(base64_encode(random_bytes(64)), 0, 64)
				];
				$this->provider()->setRememberToken($this->UID(), $rememberToken['TOKEN'], time()+$this->rememberTTL);
				(new CryptoCookie(self::COOKIE_REMEMBER, time()+$this->rememberTTL, '/', null, false, true))->write($rememberToken);
			}
		} finally {
			$this->_commit = false; // avoid double invocation on init() Exception
			sys::traceFn($prevTraceFn);
		}
	}

	/**
	 * Enable REMEMBER-TOKEN
	 */
	function enableRememberToken() {
		$this->_commit = true;
		$this->setRememberToken = true;
	}

	/**
	 * Erase AUTH data.
	 * To be invoked on LOGOUT or other required situations.
	 */
	function erase() {
		$prevTraceFn = sys::traceFn($this->_.'->erase');
		try {
			// delete AUTH-TOKEN
			switch ($this->module) {
				case 'COOKIE':
					// @TODO COOKIE module
					break;
				case 'JWT':
					sys::trace(LOG_DEBUG, T_INFO, 'erase JWT AUTH-TOKEN');
					setcookie(self::COOKIE_AUTH, '', time()-86400, '/', null, true, true);
					break;
				case 'SESSION':
					sys::trace(LOG_DEBUG, T_INFO, 'erase SESSION data');
					session_regenerate_id(false);
					unset($_SESSION['__AUTH__']);
			}

			// delete REFRESH-TOKEN
			if (isset($_COOKIE[self::COOKIE_REFRESH])) {
				sys::trace(LOG_DEBUG, T_INFO, 'erase REFRESH-TOKEN');
				try {
					$refreshToken = (new CryptoCookie(self::COOKIE_REFRESH))->read();
					$this->provider()->deleteRefreshToken($this->UID(), $refreshToken['TOKEN']);
				} catch (HttpException $Ex) {} // CryptoCookie Exception
				setcookie(self::COOKIE_REFRESH, '', time() - 86400, '/', null, false, true);
			}

			// delete REMEMBER-TOKEN
			if (isset($_COOKIE[self::COOKIE_REMEMBER])) {
				sys::trace(LOG_DEBUG, T_INFO, 'erase REMEMBER-TOKEN');
				try {
					$rememberToken = (new CryptoCookie(self::COOKIE_REMEMBER))->read();
					$this->provider()->deleteRememberToken($this->UID(), $rememberToken['TOKEN']);
				} catch (HttpException $Ex) {} // CryptoCookie Exception
				setcookie(self::COOKIE_REMEMBER, '', time()-86400, '/', null, false, true);
			}

			// regenerate XSRF-TOKEN
			sys::trace(LOG_DEBUG, T_INFO, 're-initialize XSRF-TOKEN');
			$this->_XSRF_TOKEN = substr(base64_encode(random_bytes(64)), 0, 64);
			setcookie(self::COOKIE_XSRF, $this->_XSRF_TOKEN, 0, '/', null, false, false);

			// erase data
			$this->_data = [];
			$this->_GID = $this->_GROUP = $this->_NAME = $this->_UID = null;
		} finally {
			sys::traceFn($prevTraceFn);
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
		$this->_commit = true;
		return $this;
	}

	/**
	 * Get User ID
	 * @return integer|null
	 */
	function UID() {
		return $this->_UID;
	}

	/**
	 * @return ProviderInterface
	 */
	function provider() {
		static $Provider;
		if(!$Provider) {
			try {
				$Provider = sys::context()->get('sys.AuthProvider', ProviderInterface::class);
			} catch (\Exception $Ex) {
				$Provider = new PdoProvider;
			}
		}
		return $Provider;
	}
}
