<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\util\crypto;
use const renovant\core\DATA_DIR;
use Defuse\Crypto\Crypto as DefuseCrypto,
	Defuse\Crypto\Key;

/**
 * Encryption utility using defuse/php-encryption library
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
class Crypto {

	const KEY_FILE = DATA_DIR.'crypto.key';
	static protected $key = null;

	/**
	 * @throws \Defuse\Crypto\Exception\BadFormatException
	 * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
	 */
	static protected function init() {
		if(!is_null(self::$key)) return;
		if(!file_exists(self::KEY_FILE)) {
			$Key = Key::createNewRandomKey();
			file_put_contents(self::KEY_FILE, $Key->saveToAsciiSafeString());
		}
		self::$key = Key::loadFromAsciiSafeString(file_get_contents(self::KEY_FILE));
	}

	/**
	 * Encrypt data (with automatic object JSON encoding)
	 * @param mixed $data
	 * @return string
	 * @throws \Defuse\Crypto\Exception\BadFormatException
	 * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
	 */
	static function encrypt($data) {
		self::init();
		if(is_object($data)) $data = json_encode($data);
		return DefuseCrypto::encrypt($data, self::$key);
	}


	/**
	 * Decrypt data (with automatic object JSON decoding)
	 * @param $data
	 * @return mixed
	 * @throws \Defuse\Crypto\Exception\BadFormatException
	 * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
	 * @throws \Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException
	 */
	static function decrypt($data) {
		self::init();
		$data = DefuseCrypto::decrypt($data, self::$key);
		$d = json_decode($data);
		if(json_last_error() === JSON_ERROR_NONE) return $d;
		return $data;
	}

}
