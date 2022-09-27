<?php
namespace renovant\core\auth;
use const renovant\core\trace\T_INFO;
use renovant\core\sys,
	renovant\core\http\Event as HttpEvent,
	PragmaRX\Google2FA\Google2FA;
abstract class AuthService {
	use \renovant\core\CoreTrait;

	const XSRF_COOKIE = 'XSRF-TOKEN';
	const XSRF_HEADER = 'X-XSRF-TOKEN';

	const LOGIN_UNKNOWN			= -1;
	const LOGIN_DISABLED		= -2;
	const LOGIN_PWD_INVALID		= -3;
	const LOGIN_2FA_REQUIRED	= -4;
	const LOGIN_2FA_INVALID		= -5;
	const LOGIN_EXCEPTION		= -6;

	const SET_PWD_OK		= 1;
	const SET_PWD_MISMATCH	= -1;
	const SET_PWD_EXCEPTION	= -2;

	/** Pending commit  flag */
	protected bool $_commit = false;
	/** XSRF-TOKEN value */
	protected ?string $_XSRF_TOKEN = null;

	/** Cookie XSRF-TOKEN */
	protected string $cookieXSRF = self::XSRF_COOKIE;

	/** Auth Provider
	 * @var \renovant\core\auth\provider\ProviderInterface */
	protected $Provider;

	/** REMEMBER flag */
	protected bool $rememberFlag = false;

	/** URLs to be allowed without authentication */
	protected array $authAllowUrls = [];
	/** APP modules to be skipped by authentication */
	protected array $authSkipModules = [];

	/** URLs to be allowed without XSRF token */
	protected array $xsrfSAllowUrls = [];
	/** APP modules to be skipped by XSRF */
	protected array $xsrfSkipModules = [];

	function __sleep() {
		return ['_', 'cookieXSRF', 'Provider', 'authAllowUrls', 'authSkipModules', 'xsrfSAllowUrls', 'xsrfSkipModules'];
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
	 * @throws \ReflectionException|AuthException
	 */
	function authenticateById(int $id): Auth {
		$this->doAuthenticate($this->Provider->fetchUserData($id));
		$Auth = Auth::instance();
		sys::event()->enqueue(Event::EVENT_LOGIN, new Event($Auth));
		return Auth::instance();
	}

	/**
	 * @throws \ReflectionException
	 * @internal
	 */
	protected function doAuthenticate(array $data=[]): Auth {
		$UID = $data['UID'] ?? null; unset($data['UID']);
		$GID = $data['GID'] ?? null; unset($data['GID']);
		$name = $data['NAME'] ?? null; unset($data['NAME']);
		$group = $data['GROUP'] ?? null; unset($data['GROUP']);
		return $this->authenticate($UID, $GID, $name, $group, $data);
	}

	/**
	 * @param string $login
	 * @param string $password
	 * @param string|null $otp 2FA code
	 * @param bool $remember enable REMEMBER-TOKEN
	 * @return int User ID or error code
	 */
	function checkCredentials(string $login, string $password, ?string $otp=null, bool $remember=false): int {
		try {
			$this->rememberFlag = $remember;
			$data = $this->Provider->fetchCredentials($login);
			if (!$data) return self::LOGIN_UNKNOWN;
			if ((int)$data['active'] != 1) return self::LOGIN_DISABLED;
			if (!password_verify($password, $data['password'])) return self::LOGIN_PWD_INVALID;
			if (!empty($data['tfaKey'])) {
				if(empty($otp)) return self::LOGIN_2FA_REQUIRED;
				if((new Google2FA())->verifyKey($data['tfaKey'], $otp, 1) ) return $data['user_id'];
				elseif(in_array($otp, $data['tfaRescue'])) {
					unset($data['tfaRescue'][array_search($otp, $data['tfaRescue'])]);
					$this->Provider->set2FA($data['user_id'], $data['tfaKey'], $data['tfaRescue']);
					return $data['user_id'];
				} else return self::LOGIN_2FA_INVALID;
			}
			return $data['user_id'];
		} catch (\Exception) {
			return self::LOGIN_EXCEPTION;
		}
	}

	/**
	 * Initialize AUTH module, perform Authentication & Security checks
	 * To be invoked via event listener before HTTP Controller execution (HTTP:INIT, HTTP:ROUTE or HTTP:CONTROLLER).
	 * @throws AuthException
	 * @throws \Exception
	 */
	final function init(HttpEvent $Event): void {
		$prevTraceFn = sys::traceFn($this->_.'->init');
		try {
			$Req = $Event->getRequest();
			$URI = $Req->URI();
			$APP_MOD = $Req->getAttribute('APP_MOD');

			// check AUTH
			if(!in_array($APP_MOD, $this->authSkipModules)) {
				$this->initAUTH($Event);
				$Auth = Auth::instance();

				$allowUrlFn = function (string $URI): bool {
					foreach ($this->authAllowUrls as $url)
						if(preg_match($url, $URI)) return true;
					return false;
				};
				if (!$Auth->UID() && !$allowUrlFn($URI))
					throw new AuthException(101);
			}

			// XSRF-TOKEN
			if(!in_array($APP_MOD, $this->xsrfSkipModules)) {
				$allowUrlFn = function(string $URI): bool {
					foreach ($this->xsrfSAllowUrls as $url)
						if(preg_match($url, $URI)) return true;
					return false;
				};
				if(!isset($_COOKIE[$this->cookieXSRF]))
					$this->_commit = true;
				else
					$this->_XSRF_TOKEN = $_COOKIE[$this->cookieXSRF];
				$XSRFToken = $Req->getHeader(self::XSRF_HEADER);
				if ($XSRFToken && $XSRFToken === $this->_XSRF_TOKEN)
					sys::trace(LOG_DEBUG, T_INFO, 'XSRF-TOKEN OK');
				elseif ($XSRFToken && $XSRFToken != $this->_XSRF_TOKEN)
					throw new AuthException(50);
				elseif ($URI != '/' && !$allowUrlFn($URI))
					throw new AuthException(102);
			}
		} catch (AuthException $Ex) {
			$this->_commit = true;
			throw $Ex;
		} finally {
			$this->commit(); // need on Exception to regenerate JWT/SESSION & XSRF-TOKEN
			sys::traceFn($prevTraceFn);
		}
	}

	abstract protected function initAUTH(HttpEvent $Event): void;

	/**
	 * Commit AUTH data & XSRF-TOKEN to module storage.
	 * To be invoked via event listener after HTTP Controller execution (HTTP:VIEW & HTTP:EXCEPTION).
	 * @throws \Exception
	 */
	final function commit(): void {
		if(!$this->_commit) return;
		$prevTraceFn = sys::traceFn($this->_.'->commit');
		try {
			// AUTH tokens
			$this->commitAUTH();

			// XSRF-TOKEN (cookie + header)
			if(!isset($_COOKIE[$this->cookieXSRF])) {
				sys::trace(LOG_DEBUG, T_INFO, 'initialize XSRF-TOKEN');
				$this->_XSRF_TOKEN = TokenService::generateToken();
				setcookie($this->cookieXSRF, $this->_XSRF_TOKEN, ['expires'=>0, 'path'=>'/', 'domain'=>null, 'secure'=>true, 'httponly'=>false, 'samesite'=>'Lax']);
				header(self::XSRF_HEADER.': '.$this->_XSRF_TOKEN);
			}
		} finally {
			$this->_commit = false; // avoid double invocation on init() Exception
			sys::traceFn($prevTraceFn);
		}
	}

	abstract protected function commitAUTH(): void;

	/**
	 * Erase AUTH data.
	 * To be invoked on LOGOUT or other required situations.
	 * @throws \Exception
	 */
	final function erase(): void {
		$prevTraceFn = sys::traceFn($this->_.'->erase');
		try {
			// AUTH
			$this->eraseAUTH();

			// regenerate XSRF-TOKEN
			sys::trace(LOG_DEBUG, T_INFO, 're-initialize XSRF-TOKEN');
			$this->_XSRF_TOKEN = TokenService::generateToken();
			setcookie($this->cookieXSRF, $this->_XSRF_TOKEN, ['expires'=>0, 'path'=>'/', 'domain'=>null, 'secure'=>true, 'httponly'=>false, 'samesite'=>'Lax']);

			// erase data
			$this->doAuthenticate();
		} finally {
			$this->_commit = false;
			sys::traceFn($prevTraceFn);
		}
	}

	abstract protected function eraseAUTH(): void;

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
