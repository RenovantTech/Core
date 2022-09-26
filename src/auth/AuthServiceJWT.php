<?php
namespace renovant\core\auth;
use const renovant\core\DATA_DIR;
use const renovant\core\trace\T_INFO;
use renovant\core\sys,
	renovant\core\http\CryptoCookie,
	renovant\core\http\Exception as HttpException,
	renovant\core\http\Event as HttpEvent,
	Firebase\JWT\BeforeValidException,
	Firebase\JWT\ExpiredException,
	Firebase\JWT\JWT;
class AuthServiceJWT extends AuthService {

	const COOKIE_AUTH		= 'AUTH-TOKEN';
	const COOKIE_REFRESH	= 'REFRESH-TOKEN';
	const COOKIE_REMEMBER	= 'REMEMBER-TOKEN';

	const JWT_KEY = DATA_DIR.'JWT.key';

	const TTL_AUTH		= 300;
	const TTL_REFRESH	= 86400;
	const TTL_REMEMBER	= 2592000;

	/** Cookie AUTH-TOKEN */
	protected string $cookieAUTH = self::COOKIE_AUTH;
	/** Cookie REFRESH-TOKEN */
	protected string $cookieREFRESH = self::COOKIE_REFRESH;
	/** Cookie REMEMBER-TOKEN */
	protected string $cookieREMEMBER = self::COOKIE_REMEMBER;

	/** Auth Token TTL */
	protected int $ttlAUTH = self::TTL_AUTH;
	/** Refresh Token TTL */
	protected int $ttlREFRESH = self::TTL_REFRESH;
	/** Remember Token TTL */
	protected int $ttlREMEMBER = self::TTL_REMEMBER;

	/**
	 * @throws Exception
	 * @throws \Exception
	 */
	function __construct() {
		if(!class_exists('Firebase\JWT\JWT')) throw new Exception(12);
		if(!file_exists(self::JWT_KEY))
			file_put_contents(self::JWT_KEY, base64_encode(random_bytes(64)));
	}

	function __sleep() {
		return array_merge(parent::__sleep(), ['cookieAUTH', 'cookieREFRESH', 'cookieREMEMBER', 'ttlAUTH', 'ttlREFRESH', 'ttlREMEMBER']);
	}

	function init(HttpEvent $Event) {
		$prevTraceFn = sys::traceFn($this->_.'->init');
		try {
			$ok = false;
			if (isset($_COOKIE[$this->cookieAUTH])) {
				try {
					$token = (array)JWT::decode($_COOKIE[$this->cookieAUTH], file_get_contents(self::JWT_KEY), ['HS512']);
					if (isset($token['data']) && $token['data'] = (array)$token['data']) {
						$this->doAuthenticate($token['data']);
						$this->_commit = false;
						sys::trace(LOG_DEBUG, T_INFO, 'JWT AUTH-TOKEN OK', $token['data']);
						$ok = true;
					}
				} catch (ExpiredException) {
					sys::trace(LOG_DEBUG, T_INFO, 'JWT AUTH-TOKEN exception: EXPIRED');
				} catch (BeforeValidException) {
					sys::trace(LOG_DEBUG, T_INFO, 'JWT AUTH-TOKEN exception: BEFORE-VALID');
				} catch (\Exception) { // include SignatureInvalidException, UnexpectedValueException
					sys::trace(LOG_DEBUG, T_INFO, 'JWT AUTH-TOKEN exception: INVALID');
				}
			}
			if (!$ok && isset($_COOKIE[$this->cookieREFRESH])) {
				try {
					$refreshToken = (new CryptoCookie($this->cookieREFRESH))->read();
					if($this->Provider->tokenCheck(TokenService::TOKEN_AUTH_REFRESH, $refreshToken['TOKEN'], $refreshToken['UID'])) {
						$this->doAuthenticate($this->Provider->fetchUserData($refreshToken['UID']));
						$this->_commit = true;
						sys::trace(LOG_DEBUG, T_INFO, 'JWT REFRESH-TOKEN OK');
						$ok = true;
					}
				} catch (HttpException) { // CryptoCookie Exception
					sys::trace(LOG_DEBUG, T_INFO, 'JWT REFRESH-TOKEN exception: INVALID');
				}
			}
			if (!$ok && isset($_COOKIE[$this->cookieREMEMBER])) {
				try {
					$rememberToken = (new CryptoCookie($this->cookieREMEMBER))->read();
					if($this->Provider->tokenCheck(TokenService::TOKEN_AUTH_REMEMBER, $rememberToken['TOKEN'], $rememberToken['UID'])) {
						$this->doAuthenticate($this->Provider->fetchUserData($rememberToken['UID']));
						$this->_commit = true;
						sys::trace(LOG_DEBUG, T_INFO, 'JWT REMEMBER-TOKEN OK');
					}
				} catch (HttpException) { // CryptoCookie Exception
					sys::trace(LOG_DEBUG, T_INFO, 'JWT REMEMBER-TOKEN exception: INVALID');
				}
			}
			parent::doInit($Event);
		} catch (AuthException $Ex) {
			$this->_commit = true;
			throw $Ex;
		} finally {
			$this->commit(); // need on Exception to regenerate JWT/SESSION & XSRF-TOKEN
			sys::traceFn($prevTraceFn);
		}
	}

	function commit() {
		if(!$this->_commit) return;
		$prevTraceFn = sys::traceFn($this->_.'->commit');

		$Auth = Auth::instance();
		if(!$Auth->UID()) return;
		try {
			// AUTH-TOKEN
			sys::trace(LOG_DEBUG, T_INFO, 'initialize JWT AUTH-TOKEN');
			$data = array_merge($Auth->data(), [
				'GID' => $Auth->GID(),
				'GROUP' => $Auth->GROUP(),
				'NAME' => $Auth->NAME(),
				'UID' => $Auth->UID()
			]);
			$authToken = [
				//'aud' => 'http://example.com',
				'exp' => time() + $this->ttlAUTH, // Expiry
				'iat' => time() - 1, // Issued At
				//'iss' => 'http://example.org', // Issuer
				'nbf' => time() - 1, // Not Before
				'data' => $data
			];
			setcookie($this->cookieAUTH, JWT::encode($authToken, file_get_contents(self::JWT_KEY), 'HS512'), ['expires'=>time() + $this->ttlAUTH, 'path'=>'/', 'domain'=>null, 'secure'=>true, 'httponly'=>true, 'samesite'=>'Lax']);

			// REFRESH-TOKEN
			sys::trace(LOG_DEBUG, T_INFO, 'initialize JWT REFRESH-TOKEN');
			$refreshToken = [
				'UID'	=> $Auth->UID(),
				'TOKEN'	=> TokenService::generateToken()
			];
			$this->Provider->tokenSet(TokenService::TOKEN_AUTH_REFRESH, $Auth->UID(), $refreshToken['TOKEN'], null, time()+$this->ttlREFRESH);
			(new CryptoCookie($this->cookieREFRESH, 0, '/', null, true, true))->write($refreshToken);

			// REMEMBER-TOKEN
			if($this->rememberFlag) {
				sys::trace(LOG_DEBUG, T_INFO, 'initialize JWT REMEMBER-TOKEN');
				$rememberToken = [
					'UID'	=> $Auth->UID(),
					'TOKEN'	=> TokenService::generateToken()
				];
				$this->Provider->tokenSet(TokenService::TOKEN_AUTH_REMEMBER, $Auth->UID(), $rememberToken['TOKEN'], null, time()+$this->ttlREMEMBER);
				(new CryptoCookie($this->cookieREMEMBER, time()+$this->ttlREMEMBER, '/', null, true, true))->write($rememberToken);
			}

			parent::doCommit();
		} finally {
			$this->_commit = false; // avoid double invocation on init() Exception
			sys::traceFn($prevTraceFn);
		}
	}

	function erase() {
		$prevTraceFn = sys::traceFn($this->_.'->erase');
		try {
			$Auth = Auth::instance();

			// delete AUTH-TOKEN
			sys::trace(LOG_DEBUG, T_INFO, 'erase JWT AUTH-TOKEN');
			setcookie($this->cookieAUTH, '', ['expires'=>time()-86400, 'path'=>'/', 'domain'=>null, 'secure'=>true, 'httponly'=>true, 'samesite'=>'Lax']);

			// delete REFRESH-TOKEN
			if (isset($_COOKIE[$this->cookieREFRESH])) {
				sys::trace(LOG_DEBUG, T_INFO, 'erase JWT REFRESH-TOKEN');
				try {
					$refreshToken = (new CryptoCookie($this->cookieREFRESH))->read();
					$this->Provider->tokenDelete(TokenService::TOKEN_AUTH_REFRESH, $refreshToken['TOKEN'], $Auth->UID());
				} catch (HttpException) {} // CryptoCookie Exception
				setcookie($this->cookieREFRESH, '', ['expires'=>time()-86400, 'path'=>'/', 'domain'=>null, 'secure'=>true, 'httponly'=>true, 'samesite'=>'Lax']);
			}

			// delete REMEMBER-TOKEN
			if (isset($_COOKIE[$this->cookieREMEMBER])) {
				sys::trace(LOG_DEBUG, T_INFO, 'erase JWT REMEMBER-TOKEN');
				try {
					$rememberToken = (new CryptoCookie($this->cookieREMEMBER))->read();
					$this->Provider->tokenDelete(TokenService::TOKEN_AUTH_REMEMBER, $rememberToken['TOKEN'], $Auth->UID());
				} catch (HttpException) {} // CryptoCookie Exception
				setcookie($this->cookieREMEMBER, '', ['expires'=>time()-86400, 'path'=>'/', 'domain'=>null, 'secure'=>true, 'httponly'=>true, 'samesite'=>'Lax']);
			}

			parent::doErase();
		} finally {
			$this->_commit = false;
			sys::traceFn($prevTraceFn);
		}
	}
}
