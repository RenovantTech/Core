<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\auth;
use const renovant\core\DATA_DIR;
use const renovant\core\trace\T_INFO;
use renovant\core\sys,
	renovant\core\auth\provider\PdoProvider,
	renovant\core\auth\provider\ProviderInterface,
	renovant\core\http\CryptoCookie,
	renovant\core\http\Exception as HttpException,
	renovant\core\http\Event as HttpEvent,
	Firebase\JWT\BeforeValidException,
	Firebase\JWT\ExpiredException,
	Firebase\JWT\JWT;
/**
 * Authentication Service.
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
class AuthService {
	use \renovant\core\CoreTrait;

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
	const SET_PWD_OK		= 1;
	const SET_PWD_MISMATCH	= -1;
	const SET_PWD_EXCEPTION	= -2;
	const TTL_AUTH		= 300;
	const TTL_REFRESH	= 86400;
	const TTL_RESET		= 1800;
	const TTL_REMEMBER	= 2592000;

	/** Pending commit  flag
	 * @var bool */
	protected $_commit = false;
	/** XSRF-TOKEN value
	 * @var string|null */
	protected $_XSRF_TOKEN = null;

	/** Cookie AUTH-TOKEN
	 * @var int */
	protected $cookieAUTH = self::COOKIE_AUTH;
	/** Cookie REFRESH-TOKEN
	 * @var int */
	protected $cookieREFRESH = self::COOKIE_REFRESH;
	/** Cookie REMEMBER-TOKEN
	 * @var int */
	protected $cookieREMEMBER = self::COOKIE_REMEMBER;
	/** Cookie XSRF-TOKEN
	 * @var int */
	protected $cookieXSRF = self::COOKIE_XSRF;

	/** Auth Token TTL
	 * @var int */
	protected $ttlAUTH = self::TTL_AUTH;
	/** Refresh Token TTL
	 * @var int */
	protected $ttlREFRESH = self::TTL_REFRESH;
	/** Reset Token TTL
	 * @var int */
	protected $ttlRESET = self::TTL_RESET;
	/** Remember Token TTL
	 * @var int */
	protected $ttlREMEMBER = self::TTL_REMEMBER;

	/** Active module
	 * @var string */
	protected $module = 'SESSION';
	/** Auth Provider ID
	 * @var string */
	protected $provider = 'sys.AuthProvider';
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
	 * @throws \Exception
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
		return ['_', 'cookieAUTH', 'cookieREFRESH', 'cookieREMEMBER', 'cookieXSRF', 'module', 'provider', 'ttlAUTH', 'ttlREFRESH', 'ttlREMEMBER', 'skipAuthApps', 'skipAuthUrls', 'skipXSRFApps', 'skipXSRFUrls'];
	}

	/**
	 * @param int|null $UID
	 * @param int|null $GID
	 * @param string|null $name
	 * @param string|null $group
	 * @param array $data
	 * @return Auth
	 */
	function authenticate(?int $UID, ?int $GID, ?string $name, ?string $group, array $data=[]): Auth {
		$Auth = Auth::instance();
		$RConstructor = (new \ReflectionClass(Auth::class))->getConstructor();
		$RConstructor->setAccessible(true);
		$RConstructor->invokeArgs($Auth, [$UID, $GID, $name, $group, $data]);
		$this->_commit = true;
		return $Auth;
	}

	/**
	 * @param int $id User ID
	 * @throws AuthException
	 */
	function authenticateById(int $id) {
		$this->doAuthenticate($this->provider()->fetchData($id));
	}

	/**
	 * @internal
	 * @param array $data
	 */
	protected function doAuthenticate(array $data=[]) {
		$UID = $data['UID'] ?? null; unset($data['UID']);
		$GID = $data['GID'] ?? null; unset($data['GID']);
		$name = $data['NAME'] ?? null; unset($data['NAME']);
		$group = $data['GROUP'] ?? null; unset($data['GROUP']);
		$this->authenticate($UID, $GID, $name, $group, $data);
	}

	/**
	 * Initialize AUTH module, perform Authentication & Security checks
	 * To be invoked via event listener before HTTP Controller execution (HTTP:INIT, HTTP:ROUTE or HTTP:CONTROLLER).
	 * @param HttpEvent $Event
	 * @throws AuthException
	 * @throws Exception
	 * @throws \Exception
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
					if (isset($_COOKIE[$this->cookieAUTH])) {
						try {
							$token = (array)JWT::decode($_COOKIE[$this->cookieAUTH], file_get_contents(self::JWT_KEY), ['HS512']);
							if (isset($token['data']) && $token['data'] = (array)$token['data']) {
								$this->doAuthenticate($token['data']);
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
					if (isset($_COOKIE[$this->cookieREFRESH])) {
						try {
							$refreshToken = (new CryptoCookie($this->cookieREFRESH))->read();
							if($this->provider()->checkRefreshToken($refreshToken['UID'], $refreshToken['TOKEN'])) {
								$this->doAuthenticate($this->provider()->fetchData($refreshToken['UID']));
								$this->_commit = true;
								sys::trace(LOG_DEBUG, T_INFO, 'JWT REFRESH-TOKEN OK');
								break;
							}
						} catch (HttpException $Ex) { // CryptoCookie Exception
							sys::trace(LOG_DEBUG, T_INFO, 'JWT REFRESH-TOKEN exception: INVALID');
						}
					}
					if (isset($_COOKIE[$this->cookieREMEMBER])) {
						try {
							$rememberToken = (new CryptoCookie($this->cookieREMEMBER))->read();
							if($this->provider()->checkRememberToken($rememberToken['UID'], $rememberToken['TOKEN'])) {
								$this->doAuthenticate($this->provider()->fetchData($rememberToken['UID']));
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
					if (!isset($_SESSION)) throw new Exception(23);
					if (isset($_SESSION['__AUTH__']) && is_array($_SESSION['__AUTH__'])) {
						$this->doAuthenticate($_SESSION['__AUTH__']);
						sys::trace(LOG_DEBUG, T_INFO, 'SESSION AUTH OK', $_SESSION['__AUTH__']);
					}
					break;
			}
			$Auth = Auth::instance();
			if (!$Auth->UID() && $URI != '/' && !in_array($APP, $this->skipAuthApps) && !$this->checkAUTH($URI))
				throw new AuthException(101, [$this->module]);

			// XSRF-TOKEN
			if(!isset($_COOKIE[$this->cookieXSRF]))
				$this->_commit = true;
			else
				$this->_XSRF_TOKEN = $_COOKIE[$this->cookieXSRF];
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
	 * @param string $login
	 * @param string $password
	 * @param bool $remember enable REMEMBER-TOKEN
	 * @return int
	 */
	function checkCredentials(string $login, string $password, bool $remember=false): int {
		$this->setRememberToken = $remember;
		return $this->provider()->checkCredentials($login, $password);
	}

	/**
	 * @param string $token
	 * @return integer user ID on success, 0 on ERROR
	 */
	function checkResetEmailToken(string $token): int {
		return $this->provider()->checkResetEmailToken($token);
	}

	/**
	 * @param string $token
	 * @return integer user ID on success, 0 on ERROR
	 */
	function checkResetPwdToken(string $token): int {
		return $this->provider()->checkResetPwdToken($token);
	}

	/**
	 * Commit AUTH data & XSRF-TOKEN to module storage.
	 * To be invoked via event listener after HTTP Controller execution (HTTP:VIEW & HTTP:EXCEPTION).
	 * @throws \Exception
	 */
	function commit() {
		if(!$this->_commit) return;
		$prevTraceFn = sys::traceFn($this->_.'->commit');
		try {
			// XSRF-TOKEN (cookie + header)
			sys::trace(LOG_DEBUG, T_INFO, 'initialize XSRF-TOKEN');
			$this->_XSRF_TOKEN = $this->generateToken();
			setcookie($this->cookieXSRF, $this->_XSRF_TOKEN, 0, '/', null, false, false);
			header(self::HEADER_XSRF.': '.$this->_XSRF_TOKEN);

			$Auth = Auth::instance();
			if(!$Auth->UID()) return;

			// AUTH-TOKEN
			$data = array_merge($Auth->data(), [
				'GID' => $Auth->GID(),
				'GROUP' => $Auth->GROUP(),
				'NAME' => $Auth->NAME(),
				'UID' => $Auth->UID()
			]);
			switch ($this->module) {
				case 'COOKIE':
					// @TODO COOKIE module
					break;
				case 'JWT':
					sys::trace(LOG_DEBUG, T_INFO, 'initialize JWT AUTH-TOKEN');
					$authToken = [
						//'aud' => 'http://example.com',
						'exp' => time() + $this->ttlAUTH, // Expiry
						'iat' => time() - 1, // Issued At
						//'iss' => 'http://example.org', // Issuer
						'nbf' => time() - 1, // Not Before
						'data' => $data
					];
					setcookie($this->cookieAUTH, JWT::encode($authToken, file_get_contents(self::JWT_KEY), 'HS512'), time() + $this->ttlAUTH, '/', null, true, true);
					break;
				case 'SESSION':
					sys::trace(LOG_DEBUG, T_INFO, 'update SESSION data');
					$_SESSION['__AUTH__'] = $data;
			}

			// REFRESH-TOKEN
			sys::trace(LOG_DEBUG, T_INFO, 'initialize REFRESH-TOKEN');
			$refreshToken = [
				'UID'	=> $Auth->UID(),
				'TOKEN'	=> $this->generateToken()
			];
			$this->provider()->setRefreshToken($Auth->UID(), $refreshToken['TOKEN'], time()+$this->ttlREFRESH);
			(new CryptoCookie($this->cookieREFRESH, 0, '/', null, false, true))->write($refreshToken);

			// REMEMBER-TOKEN
			if($this->setRememberToken) {
				sys::trace(LOG_DEBUG, T_INFO, 'initialize REMEMBER-TOKEN');
				$rememberToken = [
					'UID'	=> $Auth->UID(),
					'TOKEN'	=> $this->generateToken()
				];
				$this->provider()->setRememberToken($Auth->UID(), $rememberToken['TOKEN'], time()+$this->ttlREMEMBER);
				(new CryptoCookie($this->cookieREMEMBER, time()+$this->ttlREMEMBER, '/', null, false, true))->write($rememberToken);
			}
		} finally {
			$this->_commit = false; // avoid double invocation on init() Exception
			sys::traceFn($prevTraceFn);
		}
	}

	/**
	 * Erase AUTH data.
	 * To be invoked on LOGOUT or other required situations.
	 * @throws \Exception
	 */
	function erase() {
		$prevTraceFn = sys::traceFn($this->_.'->erase');
		try {
			$Auth = Auth::instance();

			// delete AUTH-TOKEN
			switch ($this->module) {
				case 'COOKIE':
					// @TODO COOKIE module
					break;
				case 'JWT':
					sys::trace(LOG_DEBUG, T_INFO, 'erase JWT AUTH-TOKEN');
					setcookie($this->cookieAUTH, '', time()-86400, '/', null, true, true);
					break;
				case 'SESSION':
					sys::trace(LOG_DEBUG, T_INFO, 'erase SESSION data');
					session_regenerate_id(false);
					unset($_SESSION['__AUTH__']);
			}

			// delete REFRESH-TOKEN
			if (isset($_COOKIE[$this->cookieREFRESH])) {
				sys::trace(LOG_DEBUG, T_INFO, 'erase REFRESH-TOKEN');
				try {
					$refreshToken = (new CryptoCookie($this->cookieREFRESH))->read();
					$this->provider()->deleteRefreshToken($Auth->UID(), $refreshToken['TOKEN']);
				} catch (HttpException $Ex) {} // CryptoCookie Exception
				setcookie($this->cookieREFRESH, '', time() - 86400, '/', null, false, true);
			}

			// delete REMEMBER-TOKEN
			if (isset($_COOKIE[$this->cookieREMEMBER])) {
				sys::trace(LOG_DEBUG, T_INFO, 'erase REMEMBER-TOKEN');
				try {
					$rememberToken = (new CryptoCookie($this->cookieREMEMBER))->read();
					$this->provider()->deleteRememberToken($Auth->UID(), $rememberToken['TOKEN']);
				} catch (HttpException $Ex) {} // CryptoCookie Exception
				setcookie($this->cookieREMEMBER, '', time()-86400, '/', null, false, true);
			}

			// regenerate XSRF-TOKEN
			sys::trace(LOG_DEBUG, T_INFO, 're-initialize XSRF-TOKEN');
			$this->_XSRF_TOKEN = $this->generateToken();
			setcookie($this->cookieXSRF, $this->_XSRF_TOKEN, 0, '/', null, false, false);

			// erase data
			$this->doAuthenticate();
		} finally {
			$this->_commit = false;
			sys::traceFn($prevTraceFn);
		}
	}

	/**
	 * @return ProviderInterface
	 */
	function provider() {
		static $Provider;
		if(!$Provider) {
			try {
				$Provider = sys::context()->get($this->provider, ProviderInterface::class);
			} catch (\Exception $Ex) {
				$Provider = new PdoProvider;
			}
		}
		return $Provider;
	}

	/**
	 * Update User password
	 * @param int $userID User ID
	 * @param string $pwd new password
	 * @param int|null $expireTime expiration time (unix timestamp)
	 * @param string|null $oldPwd old password, will be verified if provided, pass NULL to avoid checking
	 * @return integer 1 on success, negative code on ERROR
	 */
	function setPassword(int $userID, string $pwd, ?int $expireTime=null, ?string $oldPwd=null): int {
		return $this->provider()->setPassword($userID, $pwd, $expireTime, $oldPwd);
	}

	/**
	 * @param int $userID
	 * @param string $newEmail
	 * @return string RESET-TOKEN
	 */
	function setResetEmailToken(int $userID, string $newEmail): string {
		$token = $this->generateToken(64, true);
		$this->provider()->setResetEmailToken($userID, $newEmail, $token, time()+$this->ttlRESET);
		return $token;
	}

	/**
	 * @param int $userID
	 * @return string RESET-TOKEN
	 */
	function setResetPwdToken(int $userID): string {
		$token = $this->generateToken(64, true);
		$this->provider()->setResetPwdToken($userID, $token, time()+$this->ttlRESET);
		return $token;
	}

	protected function generateToken(int $length=64, bool $urlFriendly=false) {
		try {
			$token = substr(base64_encode(random_bytes($length)), 0, $length);
		} catch (\Exception $e) {
			$token = openssl_random_pseudo_bytes($length);
		}
		return $urlFriendly ? strtr($token, '+/', '-_') : $token;
	}
}
