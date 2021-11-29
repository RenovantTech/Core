<?php
namespace renovant\core\auth;
use const renovant\core\trace\T_INFO;
use renovant\core\sys,
	renovant\core\http\Event as HttpEvent;
abstract class AuthService {
	use \renovant\core\CoreTrait;

	const XSRF_COOKIE		= 'XSRF-TOKEN';
	const XSRF_HEADER		= 'X-XSRF-TOKEN';

	const LOGIN_UNKNOWN			= -1;
	const LOGIN_DISABLED		= -2;
	const LOGIN_PWD_MISMATCH	= -3;
	const LOGIN_EXCEPTION		= -4;

	const SET_PWD_OK		= 1;
	const SET_PWD_MISMATCH	= -1;
	const SET_PWD_EXCEPTION	= -2;

	/** Pending commit  flag
	 * @var bool */
	protected $_commit = false;
	/** XSRF-TOKEN value
	 * @var string|null */
	protected $_XSRF_TOKEN = null;

	/** Cookie XSRF-TOKEN
	 * @var int */
	protected $cookieXSRF = self::XSRF_COOKIE;

	/** Auth Provider
	 * @var \renovant\core\auth\provider\ProviderInterface */
	protected $Provider;

	/** REMEMBER flag
	 * @var boolean */
	protected $rememberFlag = false;
	/** APP modules to be skipped by checkAUTH()
	 * @var array */
	protected $skipAuthModules = [ 'AUTH' ];
	/** URLs to be skipped by checkAUTH()
	 * @var array */
	protected $skipAuthUrls = [];
	/** APP modules to be skipped by checkXSRF()
	 * @var array */
	protected $skipXSRFModules = [];
	/** URLs to be skipped by checkXSRF()
	 * @var array */
	protected $skipXSRFUrls = [];

	function __sleep() {
		return ['_', 'cookieXSRF', 'Provider', 'skipAuthModules', 'skipAuthUrls', 'skipXSRFModules', 'skipXSRFUrls'];
	}

	/**
	 * @throws \ReflectionException
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
	 * @throws AuthException|\ReflectionException
	 */
	function authenticateById(int $id) {
		$this->doAuthenticate($this->Provider->fetchUserData($id));
	}

	/**
	 * @throws \ReflectionException
	 * @internal
	 */
	protected function doAuthenticate(array $data=[]) {
		$UID = $data['UID'] ?? null; unset($data['UID']);
		$GID = $data['GID'] ?? null; unset($data['GID']);
		$name = $data['NAME'] ?? null; unset($data['NAME']);
		$group = $data['GROUP'] ?? null; unset($data['GROUP']);
		$this->authenticate($UID, $GID, $name, $group, $data);
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
		$this->rememberFlag = $remember;
		return $this->Provider->checkCredentials($login, $password);
	}

	/**
	 * Initialize AUTH module, perform Authentication & Security checks
	 * To be invoked via event listener before HTTP Controller execution (HTTP:INIT, HTTP:ROUTE or HTTP:CONTROLLER).
	 * @param HttpEvent $Event
	 * @throws AuthException
	 * @throws \Exception
	 */
	abstract function init(HttpEvent $Event);

	/** @throws AuthException */
	final protected function doInit(HttpEvent $Event) {
		$prevTraceFn = sys::traceFn($this->_.'->init');
		try {
			$Req = $Event->getRequest();
			$APP_MOD = $Req->getAttribute('APP_MOD');
			$URI = $Req->URI();

			$Auth = Auth::instance();
			if (!$Auth->UID() && $URI != '/' && !in_array($APP_MOD, $this->skipAuthModules) && !$this->checkAUTH($URI))
				throw new AuthException(101);

			// XSRF-TOKEN
			if(!isset($_COOKIE[$this->cookieXSRF]))
				$this->_commit = true;
			else
				$this->_XSRF_TOKEN = $_COOKIE[$this->cookieXSRF];
			$XSRFToken = $Req->getHeader(self::XSRF_HEADER);
			if ($XSRFToken && $XSRFToken === $this->_XSRF_TOKEN)
				sys::trace(LOG_DEBUG, T_INFO, 'XSRF-TOKEN OK');
			elseif ($XSRFToken && $XSRFToken != $this->_XSRF_TOKEN)
				throw new AuthException(50);
			elseif ($URI != '/' && !in_array($APP_MOD, $this->skipXSRFModules) && !$this->checkXSRF($URI))
				throw new AuthException(102);

		} catch (AuthException $Ex) {
			$this->_commit = true;
			throw $Ex;
		} finally {
			$this->commit(); // need on Exception to regenerate JWT/SESSION & XSRF-TOKEN
			sys::traceFn($prevTraceFn);
		}
	}

	/**
	 * Commit AUTH data & XSRF-TOKEN to module storage.
	 * To be invoked via event listener after HTTP Controller execution (HTTP:VIEW & HTTP:EXCEPTION).
	 * @throws \Exception
	 */
	abstract function commit();

	final protected function doCommit() {
		// XSRF-TOKEN (cookie + header)
		if(!isset($_COOKIE[$this->cookieXSRF])) {
			sys::trace(LOG_DEBUG, T_INFO, 'initialize XSRF-TOKEN');
			$this->_XSRF_TOKEN = TokenService::generateToken();
			setcookie($this->cookieXSRF, $this->_XSRF_TOKEN, 0, '/', null, true, false);
			header(self::XSRF_HEADER.': '.$this->_XSRF_TOKEN);
		}
	}

	/**
	 * Erase AUTH data.
	 * To be invoked on LOGOUT or other required situations.
	 * @throws \Exception
	 */
	abstract function erase();

	/** @throws \ReflectionException */
	final protected function doErase() {
		// regenerate XSRF-TOKEN
		sys::trace(LOG_DEBUG, T_INFO, 're-initialize XSRF-TOKEN');
		$this->_XSRF_TOKEN = TokenService::generateToken();
		setcookie($this->cookieXSRF, $this->_XSRF_TOKEN, 0, '/', null, true, false);

		// erase data
		$this->doAuthenticate();
	}

	/**
	 * Update User password
	 * @param int $userID User ID
	 * @param bool $active
	 * @return integer 1 on success, negative code on ERROR
	 */
	function setActive(int $userID, bool $active): int {
		return $this->Provider->setActive($userID, $active);
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
		return $this->Provider->setPassword($userID, $pwd, $expireTime, $oldPwd);
	}
}
