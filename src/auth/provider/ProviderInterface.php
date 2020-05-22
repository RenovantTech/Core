<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\auth\provider;
use renovant\core\auth\AuthException;
/**
 * Authentication Provider interface.
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
interface ProviderInterface {

	/**
	 * Fetch user data
	 * @param integer $id User ID
	 * @return array
	 * @throws AuthException
	 */
	function fetchData(int $id): array;

	/**
	 * Perform login
	 * @param string $login
	 * @param string $password
	 * @return integer user ID on success, negative code on ERROR
	 */
	function checkCredentials($login, $password): int;

	/**
	 * Check REFRESH-TOKEN validity
	 * @param $userId
	 * @param $token
	 * @return bool TRUE if valid
	 */
	function checkRefreshToken($userId, $token): bool;

	/**
	 * Check RESET-TOKEN validity
	 * @param $token
	 * @return integer user ID on success, 0 on ERROR
	 */
	function checkResetEmailToken($token): int;

	/**
	 * Check RESET-TOKEN validity
	 * @param $token
	 * @return integer user ID on success, 0 on ERROR
	 */
	function checkResetPwdToken($token): int;

	/**
	 * Check REMEMBER-TOKEN validity
	 * @param $userId
	 * @param $token
	 * @return bool TRUE if valid
	 */
	function checkRememberToken($userId, $token): bool;

	/**
	 * Delete REFRESH-TOKEN
	 * @param int $userId User ID
	 * @param string $token
	 * @return bool
	 */
	function deleteRefreshToken($userId, $token): bool;

	/**
	 * Delete REMEMBER-TOKEN
	 * @param int $userId User ID
	 * @param string $token
	 * @return bool
	 */
	function deleteRememberToken($userId, $token): bool;

	/**
	 * @param int $userId
	 * @param string $pwd
	 * @param int|null $expireTime expiration time (unix timestamp)
	 * @param string|null $oldPwd
	 * @return integer 1 on success, negative code on ERROR
	 */
	function setPassword(int $userId, string $pwd, ?int $expireTime=null, ?string $oldPwd=null): int;

	/**
	 * Store new REFRESH-TOKEN value
	 * @param int $userId User ID
	 * @param string $token
	 * @param int $expireTime expiration time (unix timestamp)
	 */
	function setRefreshToken($userId, $token, $expireTime);

	/**
	 * Store new RESET-TOKEN value
	 * @param int $userId User ID
	 * @param string $newEmail
	 * @param string $token
	 * @param int $expireTime expiration time (unix timestamp)
	 */
	function setResetEmailToken($userId, $newEmail, $token, $expireTime);

	/**
	 * Store new RESET-TOKEN value
	 * @param int $userId User ID
	 * @param string $token
	 * @param int $expireTime expiration time (unix timestamp)
	 */
	function setResetPwdToken($userId, $token, $expireTime);

	/**
	 * Store new REMEMBER-TOKEN value
	 * @param int $userId User ID
	 * @param string $token
	 * @param int $expireTime expiration time (unix timestamp)
	 */
	function setRememberToken($userId, $token, $expireTime);
}
