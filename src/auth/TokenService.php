<?php
namespace renovant\core\auth;
use renovant\core\auth\provider\ProviderInterface;
class TokenService {
	use \renovant\core\CoreTrait;

	const TOKEN_ACTIVATE_USER	= 'ACTIVATE-USER';
	const TOKEN_AUTH_REFRESH	= 'AUTH-REFRESH';
	const TOKEN_AUTH_REMEMBER	= 'AUTH-REMEMBER';
	const TOKEN_RESET_EMAIL		= 'RESET-EMAIL';
	const TOKEN_RESET_PWD		= 'RESET-PWD';

	const TTL_ACTIVATE			= 86400;
	const TTL_RESET				= 1800;

	/** @var ProviderInterface */
	protected $Provider;
	/** Activate Token TTL
	 * @var int */
	protected $ttlACTIVATE = self::TTL_ACTIVATE;
	/** Reset Token TTL
	 * @var int */
	protected $ttlRESET = self::TTL_RESET;

	/**
	 * @param string $token
	 * @return integer user ID on success, 0 on ERROR
	 */
	function checkActivateUserToken(string $token): int {
		$data = $this->Provider->tokenFetch(self::TOKEN_ACTIVATE_USER, $token);
		if(!is_array($data)) return 0;
		list($userID, ) = $data;
		$this->Provider->setActive($userID, true);
//		$this->Provider->tokenDelete(self::TOKEN_ACTIVATE_USER, $token, $userID);
		return (int) $userID;
	}

	/**
	 * @param string $token
	 * @return integer user ID on success, 0 on ERROR
	 */
	function checkResetEmailToken(string $token): int {
		$data = $this->Provider->tokenFetch(self::TOKEN_RESET_EMAIL, $token);
		if(!is_array($data)) return 0;
		list($userID, $newEmail) = $data;
		$this->Provider->setEmail($userID, $newEmail);
		$this->Provider->tokenDelete(self::TOKEN_RESET_EMAIL, $token, $userID);
		return (int) $userID;
	}

	/**
	 * @param string $token
	 * @return integer user ID on success, 0 on ERROR
	 */
	function checkResetPwdToken(string $token): int {
		$data = $this->Provider->tokenFetch(self::TOKEN_RESET_PWD, $token);
		if(!is_array($data)) return 0;
		list($userID, ) = $data;
		$this->Provider->tokenDelete(self::TOKEN_RESET_PWD, $token, $userID);
		return (int) $userID;
	}

	/**
	 * @param int $userID
	 * @return string ACTIVATE-TOKEN
	 */
	function setActivateUserToken(int $userID): string {
		$token = self::generateToken(64, true);
		$this->Provider->tokenSet(self::TOKEN_ACTIVATE_USER, $userID, $token, null, time()+$this->ttlACTIVATE);
		return $token;
	}

	/**
	 * @param int $userID
	 * @param string $newEmail
	 * @return string RESET-TOKEN
	 */
	function setResetEmailToken(int $userID, string $newEmail): string {
		$token = self::generateToken(64, true);
		$this->Provider->tokenSet(self::TOKEN_RESET_EMAIL, $userID, $token, $newEmail, time()+$this->ttlRESET);
		return $token;
	}

	/**
	 * @param int $userID
	 * @return string RESET-TOKEN
	 */
	function setResetPwdToken(int $userID): string {
		$token = self::generateToken(64, true);
		$this->Provider->tokenSet(self::TOKEN_RESET_PWD, $userID, $token, null, time()+$this->ttlRESET);
		return $token;
	}

	static function generateToken(int $length=64, bool $urlFriendly=false) {
		try {
			$token = substr(base64_encode(random_bytes($length)), 0, $length);
		} catch (\Exception $e) {
			$token = openssl_random_pseudo_bytes($length);
		}
		return $urlFriendly ? strtr($token, '+/', '-_') : $token;
	}
}
