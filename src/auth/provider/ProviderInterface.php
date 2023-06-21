<?php
namespace renovant\core\auth\provider;
use renovant\core\auth\AuthException;
interface ProviderInterface {

	/**
	 * Perform login
	 * @param string $login
	 * @return array|null
	 * @throws \PDOException
	 */
	function fetchCredentials(string $login): ?array;

	function disable2FA(int $userID): bool;

	function isEnabled2FA(int $userID): bool;

	/**
	 * Fetch User data
	 * @param integer $id User ID
	 * @return array|null
	 * @throws AuthException
	 */
	function fetchUserData(int $id): ?array;

	/**
	 * @param int $userID
	 * @param bool $active
	 * @return integer 1 on success, negative code on ERROR
	 */
	function setActive(int $userID, bool $active): int;

	/**
	 * @param int $userID
	 * @param string $email
	 * @return integer 1 on success, negative code on ERROR
	 */
	function setEmail(int $userID, string $email): int;

	/**
	 * @param int $userID
	 * @param string $pwd
	 * @param int|null $expireTime expiration time (unix timestamp)
	 * @param string|null $currPwd
	 * @return integer 1 on success, negative code on ERROR
	 */
	function setPassword(int $userID, string $pwd, ?int $expireTime=null, ?string $currPwd=null): int;

	/**
	 * Set 2FA secret key
	 * @param integer $userID User ID
	 * @param string $secretKey
	 * @param array $rescueCodes
	 * @return bool
	 * @throws AuthException
	 */
	function set2FA(int $userID, string $secretKey, array $rescueCodes): bool;

	/**
	 * Check TOKEN validity
	 * @param string $tokenType
	 * @param string $token
	 * @param int $userID
	 * @return bool TRUE if valid
	 */
	function tokenCheck(string $tokenType, string $token, int $userID): bool;

	/**
	 * Delete TOKEN
	 * @param string $tokenType
	 * @param string $token
	 * @param int $userID User ID
	 * @return bool
	 */
	function tokenDelete(string $tokenType, string $token, int $userID): bool;

	/**
	 * Fetch TOKEN data
	 * @param string $tokenType
	 * @param string $token
	 * @return array|null userID & data if valid, NULL if invalid
	 */
	function tokenFetch(string $tokenType, string $token): ?array;

	/**
	 * Store new TOKEN value
	 * @param string $tokenType
	 * @param int $userID User ID
	 * @param string $token
	 * @param string|null $data
	 * @param int $expireTime expiration time (unix timestamp)
	 */
	function tokenSet(string $tokenType, int $userID, string $token, ?string $data, int $expireTime);
}
