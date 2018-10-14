<?php
namespace test\util\crypto;
use renovant\core\util\crypto\Crypto;

class CryptoTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @throws \Defuse\Crypto\Exception\BadFormatException
	 * @throws \Defuse\Crypto\Exception\EnvironmentIsBrokenException
	 * @throws \Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException
	 */
	function testEncryptDecrypt() {
		$data ='ASHFERUFFKJFSKJFDSF';
		$crypted = Crypto::encrypt($data);
		$this->assertEquals($data, Crypto::decrypt($crypted));
	}
}
