<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\http;
use const metadigit\core\DATA_DIR;
/**
 * Crypt Cookie using sodium library
 * @author Daniele Sciacchitano <dan@metadigit.it>
 * @see https://paragonie.com/book/pecl-libsodium/read/09-recipes.md#encrypted-cookies
 */
class CryptoCookie {

	const KEY_FILE = DATA_DIR.'cookie.key';

	/**
	 * @var string|null */
	protected $key = null;
	/** Cookie name
	 * @var string */
	protected $name;

	function __construct($name) {
		$this->name = $name;
		if(file_exists(self::KEY_FILE)) {
			$this->key = file_get_contents(self::KEY_FILE);
		} else {
			$this->key = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
			file_put_contents(self::KEY_FILE, $this->key);
		}
	}

	/**
	 * Reads an encrypted cookie
	 * @return mixed
	 * @throws Exception
	 */
	function read() {
		if(!array_key_exists($this->name, $_COOKIE)) return null;
		$cookie = sodium_hex2bin($_COOKIE[$this->name]);
		list ($encKey, $authKey) = $this->splitKeys();
		$mac = mb_substr($cookie, 0, SODIUM_CRYPTO_AUTH_BYTES, '8bit');
		$nonce = mb_substr($cookie, SODIUM_CRYPTO_AUTH_BYTES, SODIUM_CRYPTO_STREAM_NONCEBYTES, '8bit');
		$cipherText = mb_substr($cookie, SODIUM_CRYPTO_AUTH_BYTES + SODIUM_CRYPTO_STREAM_NONCEBYTES, null, '8bit');
		if (sodium_crypto_auth_verify($mac, $nonce . $cipherText, $authKey)) {
			sodium_memzero($authKey);
			$data = sodium_crypto_stream_xor($cipherText, $nonce, $encKey);
			sodium_memzero($encKey);
			if($data !== false)
				return unserialize($data);
		} else {
			sodium_memzero($authKey);
			sodium_memzero($encKey);
		}
		throw new Exception('Decryption failed.');
	}

	/**
	 * Writes an encrypted cookie
	 * @param mixed $data
	 * @return bool
	 */
	function write($data) {
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
		return setcookie($this->name, $cryptData);
	}

	/**
	 * Just an example. In a real system, you want to use HKDF for
	 * key-splitting instead of just a keyed BLAKE2b hash.
	 * @return array(2) [encryption key, authentication key]
	 */
	private function splitKeys() {
		$encKey = sodium_crypto_generichash(
			sodium_crypto_generichash('encryption', str_pad($this->name, 16, '_')),
			$this->key,
			SODIUM_CRYPTO_STREAM_KEYBYTES
		);
		$authKey = sodium_crypto_generichash(
			sodium_crypto_generichash('authentication', str_pad($this->name, 16, '_')),
			$this->key,
			SODIUM_CRYPTO_AUTH_KEYBYTES
		);
		return [$encKey, $authKey];
	}
}
