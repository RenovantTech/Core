<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\auth\provider;
use const renovant\core\trace\T_INFO;
use renovant\core\sys,
	renovant\core\auth\AuthException,
	renovant\core\auth\AuthService;
/**
 * Authentication Provider via PDO.
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
class PdoProvider implements ProviderInterface {
	use \renovant\core\CoreTrait;

	const SQL_AUTHENTICATE = 'SELECT %s FROM %s WHERE id = :id';
	const SQL_CHECK_REFRESH_TOKEN = 'SELECT COUNT(*) FROM `%s` WHERE type = "REFRESH" AND user_id = :user_id AND token = :token AND expire >= NOW()';
	const SQL_CHECK_REMEMBER_TOKEN = 'SELECT COUNT(*) FROM `%s` WHERE type = "REMEMBER" AND user_id = :user_id AND token = :token AND expire >= NOW()';
	const SQL_DELETE_REFRESH_TOKEN = 'DELETE FROM `%s` WHERE type = "REFRESH" AND user_id = :user_id AND token = :token';
	const SQL_DELETE_REMEMBER_TOKEN = 'DELETE FROM `%s` WHERE type = "REMEMBER" AND user_id = :user_id AND token = :token';
	const SQL_LOGIN = 'SELECT user_id, active, password FROM `%s` WHERE login = :login';
	const SQL_SET_REFRESH_TOKEN = 'INSERT INTO `%s` (type, user_id, token, expire) VALUES ("REFRESH", :user_id, :token, :expire)';
	const SQL_SET_REMEMBER_TOKEN = 'INSERT INTO `%s` (type, user_id, token, expire) VALUES ("REMEMBER", :user_id, :token, :expire)';

	/** User table fields to load into AUTH data on login
	 * @var string */
	protected $fields = '*';
	/** PDO instance ID
	 * @var string */
	protected $pdo = 'master';
	/** DB tables
	 * @var array */
	protected $tables = [
		'auth'		=> 'sys_auth',
		'tokens'	=> 'sys_tokens',
		'users'		=> 'sys_users'
	];

	/**
	 * PdoProvider constructor.
	 * @param string $pdo PDO instance ID, default to "master"
	 * @param array|null $tables
	 */
	function __construct($pdo='master', array $tables=null) {
		$prevTraceFn = sys::traceFn('sys.AuthProvider');
		if ($pdo) $this->pdo = $pdo;
		if ($tables) $this->tables = array_merge($this->tables, $tables);
		try {
			sys::trace(LOG_DEBUG, T_INFO, 'initialize AUTH storage');
			$PDO = sys::pdo($this->pdo);
			$driver = $PDO->getAttribute(\PDO::ATTR_DRIVER_NAME);
			$PDO->exec(str_replace(
				['t_auth', 't_tokens', 't_users'],
				[$this->tables['auth'], $this->tables['tokens'], $this->tables['users']],
				file_get_contents(__DIR__ . '/sql/init-' . $driver . '.sql')
			));
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	/**
	 * @param integer $id User ID
	 * @return array
	 * @throws AuthException
	 */
	function fetchData(int $id): array {
		$prevTraceFn = sys::traceFn($this->_.'->authenticateById');
		try {
			$data = sys::pdo($this->pdo)->prepare(sprintf(self::SQL_AUTHENTICATE, $this->fields, $this->tables['users']))
				->execute(['id'=>$id])->fetch(\PDO::FETCH_ASSOC);
			if(!is_array($data)) throw new AuthException(103);
			unset($data['id']);
			$GID = $data['gid'] ?? null; unset($data['gid']);
			$NAME = ($data['name']??'').' '.($data['surname']??''); unset($data['name']); unset($data['surname']);
			$GROUP = $data['group'] ?? null; unset($data['group']);
			return array_merge($data, [
				'UID' => $id,
				'GID' => $GID,
				'NAME'=> $NAME,
				'GROUP'=>$GROUP
			]);
		} catch (\Exception $Ex) {
			throw new AuthException(103);
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	function checkCredentials($login, $password): int {
		$prevTraceFn = sys::traceFn($this->_.'->checkCredentials');
		try {
			$data = sys::pdo($this->pdo)->prepare(sprintf(self::SQL_LOGIN, $this->tables['auth']))
				->execute(['login'=>$login])->fetch(\PDO::FETCH_ASSOC);
			if(!$data) return AuthService::LOGIN_UNKNOWN;
			if((int)$data['active'] != 1) return AuthService::LOGIN_DISABLED;
			if(!password_verify($password, $data['password'])) return AuthService::LOGIN_PWD_MISMATCH;
			return $data['user_id'];
		} catch (\Exception $Ex) {
			return AuthService::LOGIN_EXCEPTION;
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	function checkRefreshToken($userId, $token): bool {
		$prevTraceFn = sys::traceFn($this->_.'->checkRefreshToken');
		try {
			return (bool) sys::pdo($this->pdo)->prepare(sprintf(self::SQL_CHECK_REFRESH_TOKEN, $this->tables['tokens']))
				->execute(['user_id'=>$userId, 'token'=>$token])->fetchColumn();
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	function checkRememberToken($userId, $token): bool {
		$prevTraceFn = sys::traceFn($this->_.'->checkRememberToken');
		try {
			return (bool) sys::pdo($this->pdo)->prepare(sprintf(self::SQL_CHECK_REMEMBER_TOKEN, $this->tables['tokens']))
				->execute(['user_id'=>$userId, 'token'=>$token])->fetchColumn();
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	function deleteRefreshToken($userId, $token): bool {
		$prevTraceFn = sys::traceFn($this->_.'->deleteRefreshToken');
		try {
			return (bool) sys::pdo($this->pdo)->prepare(sprintf(self::SQL_DELETE_REFRESH_TOKEN, $this->tables['tokens']))
				->execute(['user_id'=>$userId, 'token'=>$token])->rowCount();
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	function deleteRememberToken($userId, $token): bool {
		$prevTraceFn = sys::traceFn($this->_.'->deleteRememberToken');
		try {
			return (bool) sys::pdo($this->pdo)->prepare(sprintf(self::SQL_DELETE_REMEMBER_TOKEN, $this->tables['tokens']))
				->execute(['user_id'=>$userId, 'token'=>$token])->rowCount();
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	function setRefreshToken($userId, $token, $expireTime) {
		$prevTraceFn = sys::traceFn($this->_.'->setRefreshToken');
		try {
			$expire = $expireTime ? strftime('%F %T', $expireTime) : null;
			sys::pdo($this->pdo)->prepare(sprintf(self::SQL_SET_REFRESH_TOKEN, $this->tables['tokens']))
				->execute(['user_id'=>$userId, 'token'=>$token, 'expire'=>$expire]);
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	function setRememberToken($userId, $token, $expireTime) {
		$prevTraceFn = sys::traceFn($this->_.'->setRememberToken');
		try {
			$expire = $expireTime ? strftime('%F %T', $expireTime) : null;
			sys::pdo($this->pdo)->prepare(sprintf(self::SQL_SET_REMEMBER_TOKEN, $this->tables['tokens']))
				->execute(['user_id'=>$userId, 'token'=>$token, 'expire'=>$expire]);
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}
}
