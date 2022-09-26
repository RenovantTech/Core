<?php
namespace renovant\core\http;
use const renovant\core\DATA_DIR;
/**
 * Crypt Cookie using sodium library
 * @see https://paragonie.com/book/pecl-libsodium/read/09-recipes.md#encrypted-cookies
 */
class CryptoCookie {

	const KEY_FILE = DATA_DIR.'cookie.key';

	/** Encryption KEY */
	protected ?string $_key = null;
	/** Cookie domain */
	protected string $domain;
	/** Cookie expire time */
	protected int $expire;
	/** Cookie HTTP flag */
	protected bool $httpOnly;
	/** Cookie name */
	protected string $name;
	/** Cookie path */
	protected string $path;
	/** Cookie secure flag */
	protected bool $secure;
	/** Cookie sameSite flag */
	protected string $sameSite='Lax';

	/**
	 * CryptoCookie constructor.
	 * @throws \Exception
	 */
	function __construct(string $name, int $expire=0, string $path='', ?string $domain='', bool $secure=true, bool $httpOnly=false, string $sameSite='Lax') {
		$this->name = $name;
		$this->expire = $expire;
		$this->path = $path;
		$this->domain = (string) $domain;
		$this->secure = $secure;
		$this->httpOnly = $httpOnly;
		$this->sameSite = $sameSite;
		if(file_exists(self::KEY_FILE))
			$this->_key = file_get_contents(self::KEY_FILE);
		else {
			$this->_key = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
			file_put_contents(self::KEY_FILE, $this->_key);
		}
	}

	/**
	 * Reads an encrypted cookie
	 * @return mixed
	 * @throws Exception
	 */
	function read() {
		if(!array_key_exists($this->name, $_COOKIE)) return null;
		try {
			$cookie = sodium_hex2bin($_COOKIE[$this->name]);
			list ($encKey, $authKey) = $this->splitKeys();
			$mac = mb_substr($cookie, 0, SODIUM_CRYPTO_AUTH_BYTES, '8bit');
			$nonce = mb_substr($cookie, SODIUM_CRYPTO_AUTH_BYTES, SODIUM_CRYPTO_STREAM_NONCEBYTES, '8bit');
			$cipherText = mb_substr($cookie, SODIUM_CRYPTO_AUTH_BYTES + SODIUM_CRYPTO_STREAM_NONCEBYTES, null, '8bit');
			if (sodium_crypto_auth_verify($mac, $nonce . $cipherText, $authKey)) {
				sodium_memzero($authKey);
				$data = sodium_crypto_stream_xor($cipherText, $nonce, $encKey);
				sodium_memzero($encKey);
				if($data != false)
					return unserialize($data);
			} else {
				sodium_memzero($authKey);
				sodium_memzero($encKey);
			}
			throw new Exception(401);
		} catch (\SodiumException) {
			throw new Exception(402);
		}
	}

	/**
	 * Writes an encrypted cookie
	 * @throws \Exception
	 */
	function write(mixed $data): bool {
		$data = serialize($data);
		$nonce = random_bytes(SODIUM_CRYPTO_STREAM_NONCEBYTES);
		list($encKey, $authKey) = $this->splitKeys();
		$cipherText = sodium_crypto_stream_xor($data, $nonce, $encKey);
		sodium_memzero($data);
		$mac = sodium_crypto_auth($nonce . $cipherText, $authKey);
		sodium_memzero($encKey);
		sodium_memzero($authKey);
		$cryptData = sodium_bin2hex($mac.$nonce.$cipherText);
		$_COOKIE[$this->name] = $cryptData;
		return setcookie($this->name, $cryptData, ['expires'=>$this->expire, 'path'=>$this->path, 'domain'=>$this->domain, 'secure'=>$this->secure, 'httponly'=>$this->httpOnly, 'samesite'=>$this->sameSite]);
	}

	/**
	 * Just an example. In a real system, you want to use HKDF for
	 * key-splitting instead of just a keyed BLAKE2b hash.
	 * @return array(2) [encryption key, authentication key]
	 * @throws \SodiumException
	 */
	private function splitKeys(): array {
		$encKey = sodium_crypto_generichash(
			sodium_crypto_generichash('encryption', str_pad($this->name, 16, '_')),
			$this->_key,
			SODIUM_CRYPTO_STREAM_KEYBYTES
		);
		$authKey = sodium_crypto_generichash(
			sodium_crypto_generichash('authentication', str_pad($this->name, 16, '_')),
			$this->_key,
			SODIUM_CRYPTO_AUTH_KEYBYTES
		);
		return [$encKey, $authKey];
	}
}
