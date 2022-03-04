<?php
namespace renovant\core\util\crypto;
use const renovant\core\DATA_DIR;
class Crypto {

	const KEY_FILE = DATA_DIR.'crypto.key';
	static protected $key = null;

	/** @throws \Exception */
	static protected function init() {
		if(!is_null(self::$key)) return;

		if(file_exists(self::KEY_FILE))
			self::$key = file_get_contents(self::KEY_FILE);
		else {
			self::$key = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
			file_put_contents(self::KEY_FILE, self::$key);
		}
	}

	/**
	 * Encrypt data (with automatic object JSON encoding)
	 * @param mixed $data
	 * @param bool $bin2hex
	 * @return string
	 * @throws \SodiumException
	 * @throws \Exception
	 */
	static function encrypt($data, bool $bin2hex=false) {
		self::init();
		$data = serialize($data);
		$nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
		$cipherText = sodium_crypto_secretbox($data, $nonce, self::$key);
		return $bin2hex ? sodium_bin2hex($nonce.$cipherText) : $nonce.$cipherText;
	}

	/**
	 * Decrypt data (with automatic object JSON decoding)
	 * @param $cryptData
	 * @param bool $hex2bin
	 * @return mixed
	 * @throws \SodiumException
	 */
	static function decrypt($cryptData, bool $hex2bin=false) {
		self::init();
		$data = $hex2bin ? sodium_hex2bin($cryptData) : $cryptData;
		$nonce = mb_substr($data, 0, SODIUM_CRYPTO_STREAM_NONCEBYTES, '8bit');
		$cipherText = mb_substr($data, SODIUM_CRYPTO_STREAM_NONCEBYTES, null, '8bit');
		$data = sodium_crypto_secretbox_open($cipherText, $nonce, self::$key);
		return unserialize($data);
	}
}
