<?php
namespace renovant\core\auth;

use renovant\core\auth\provider\ProviderInterface;
use renovant\core\util\UUID;
use BaconQrCode\Writer;
use BaconQrCode\Renderer\Image\Png;
use PragmaRX\Google2FA\Google2FA;

class Auth2FA {
	use \renovant\core\CoreTrait;

	public const KEY_ALGORITHM = 'sha512';
	public const KEY_LENGTH    = 32;
	public const QRCODE_DIM    = 200;

	/** @var ProviderInterface */
	protected $Provider;

	/**
	 * @throws AuthException
	 */
	public function enable(int $userID, string $secretKey, array $rescueCodes): bool {
		return $this->Provider->set2FA($userID, $secretKey, $rescueCodes);
	}

	public function disable(int $userID): bool {
		return $this->Provider->disable2FA($userID);
	}

	public function isEnabled(int $userID): bool {
		return $this->Provider->isEnabled2FA($userID);
	}

	/**
	 * @param int $keyLength
	 * @return string Secret Key
	 * @throws AuthException
	 */
	public function generateSecretKey(int $keyLength = self::KEY_LENGTH): string {
		$Google2FA = new Google2FA();
		$Google2FA->setAlgorithm(self::KEY_ALGORITHM);
		return $Google2FA->generateSecretKey($keyLength);
	}

	/**
	 * @return array rescue codes
	 */
	public function generateRescueCodes(int $qty = 10): array {
		$codes = [];
		for ($i = 1; $i <= $qty; $i++) {
			$codes[] = UUID::v4();
		}
		return $codes;
	}

	/**
	 * @param int $userID
	 * @param string $code
	 * @return boolean TRUE on success, FALSE on ERROR
	 * @throws AuthException|\SodiumException
	 */
	public function checkCode(int $userID, string $code): bool {
		list($secretKey, $rescueCodes) = $this->Provider->fetch2FA($userID);
		$Google2FA                     = new Google2FA();
		return $Google2FA->verifyKey($secretKey, $code, 1);
	}

	/**
	 * @param string $code
	 * @param string $secretKey
	 * @return boolean TRUE on success, FALSE on ERROR
	 * @throws AuthException
	 */
	public function isValid(string $code, string $secretKey): bool {
		$Google2FA = new Google2FA();
		return (bool) $Google2FA->verifyKey($secretKey, $code, 1);
	}

	/**
	 * @param string $email
	 * @param string $serviceName
	 * @param string $secretKey
	 * @param int $dimension QR Code pixel width & height
	 * @return string QR Code base64 encoded
	 */
	public function qrCode(string $email, string $serviceName, string $secretKey, int $dimension = self::QRCODE_DIM): string {
		$Google2FA = new Google2FA();
		$qrCodeUrl = $Google2FA->getQRCodeUrl($serviceName, $email, $secretKey);
		$Writer    = new Writer((new Png())->setWidth($dimension)->setHeight($dimension));
		return base64_encode($Writer->writeString($qrCodeUrl));
	}
}
