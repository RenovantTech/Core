<?php
namespace renovant\core\auth;
use renovant\core\auth\provider\ProviderInterface,
	renovant\core\util\UUID,
	BaconQrCode\Writer,
	BaconQrCode\Renderer\Image\Png,
	PragmaRX\Google2FA\Google2FA;

class Auth2FA {
	use \renovant\core\CoreTrait;

	const KEY_ALGORITHM	= 'sha512';
	const KEY_LENGTH	= 32;
	const QRCODE_DIM	= 200;

	/** @var ProviderInterface */
	protected $Provider;

	/**
	 * @throws AuthException
	 */
	function enable(int $userID, string $secretKey, array $rescueCodes): bool {
		return $this->Provider->set2FA($userID, $secretKey, $rescueCodes);
	}

	function disable(int $userID): bool {
		return $this->Provider->disable2FA($userID);
	}

	function isEnabled(int $userID): bool {
		return $this->Provider->isEnabled2FA($userID);
	}

	/**
	 * @param int $keyLength
	 * @return string Secret Key
	 * @throws AuthException
	 */
	function generateSecretKey(int $keyLength=self::KEY_LENGTH): string {
		$Google2FA = new Google2FA();
		$Google2FA->setAlgorithm(self::KEY_ALGORITHM);
		return $Google2FA->generateSecretKey($keyLength);
	}

	/**
	 * @return array rescue codes
	 */
	function generateRescueCodes(int $qty = 10): array {
		$codes = [];
		for($i=1; $i<=$qty; $i++) {
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
	function checkCode(int $userID, string $code): bool {
		list($secretKey, $rescueCodes) = $this->Provider->fetch2FA($userID);
		$Google2FA = new Google2FA();
		return $Google2FA->verifyKey($secretKey, $code, 1);
	}

	/**
	 * @param string $code
	 * @param string $secretKey
	 * @return boolean TRUE on success, FALSE on ERROR
	 * @throws AuthException
	 */
	function isValid(string $code, string $secretKey): bool {
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
	function qrCode(string $email, string $serviceName, string $secretKey, int $dimension=self::QRCODE_DIM): string {
		$Google2FA = new Google2FA();
		$qrCodeUrl = $Google2FA->getQRCodeUrl($serviceName, $email, $secretKey);
		$Writer = new Writer((new \BaconQrCode\Renderer\Image\Png())->setWidth($dimension)->setHeight($dimension));
		return base64_encode($Writer->writeString($qrCodeUrl));
	}
}
