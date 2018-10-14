<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace metadigit\core\auth\provider;
/**
 * Authentication Provider interface.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
interface ProviderInterface {

	/**
	 * Authenticate User by credentials
	 * @param string $login
	 * @param string $password
	 * @return int 1 on SUCCESS, negative code on ERROR
	 */
	function authenticate($login, $password): int;

	/**
	 * Authenticate User by ID
	 * @param int $id User ID
	 * @return bool
	 */
	function authenticateById($id): bool;

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
	 */
	function deleteRefreshToken($userId, $token);

	/**
	 * Delete REMEMBER-TOKEN
	 * @param int $userId User ID
	 * @param string $token
	 */
	function deleteRememberToken($userId, $token);

	/**
	 * Store new REFRESH-TOKEN value
	 * @param int $userId User ID
	 * @param string $token
	 * @param int $expireTime expiration time (unix timestamp)
	 */
	function setRefreshToken($userId, $token, $expireTime);

	/**
	 * Store new REMEMBER-TOKEN value
	 * @param int $userId User ID
	 * @param string $token
	 * @param int $expireTime expiration time (unix timestamp)
	 */
	function setRememberToken($userId, $token, $expireTime);
}
