<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\auth\provider;
use const metadigit\core\trace\T_INFO;
use metadigit\core\sys,
	metadigit\core\auth\AUTH;
/**
 * Authentication Provider via PDO.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class PdoProvider implements ProviderInterface {
	use \metadigit\core\CoreTrait;

	const SQL_INIT = '
		CREATE TABLE IF NOT EXISTS `{auth}` (
			id				INT UNSIGNED NOT NULL,
			active			TINYINT(1) NOT NULL DEFAULT 0,
			login			VARCHAR(30) NULL DEFAULT NULL,
			password		VARCHAR(255) NULL DEFAULT NULL,
			passwordExpire	DATETIME NULL DEFAULT NULL,
			refreshToken	CHAR(64) NULL DEFAULT NULL,
			refreshExpire	DATETIME NULL DEFAULT NULL,
			resetToken		CHAR(64) NULL DEFAULT NULL,
			resetExpire		DATETIME NULL DEFAULT NULL,
			PRIMARY KEY (id),
			CONSTRAINT uk_sys_auth_login UNIQUE KEY (login),
			CONSTRAINT fk_sys_auth_id FOREIGN KEY (id) REFERENCES `{users}` (id) ON DELETE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		
		DROP TRIGGER IF EXISTS triggerAI_{users};
		CREATE TRIGGER triggerAI_{users} AFTER INSERT ON {users}
			FOR EACH ROW BEGIN
				INSERT INTO {auth} (id) VALUES (NEW.id);
			END;
	';

	const SQL_AUTHENTICATE = 'SELECT %s FROM %s WHERE id = :id';
	const SQL_CHECK_REFRESH_TOKEN = 'SELECT COUNT(*) FROM `%s` WHERE id = :id AND refreshToken = :token AND refreshExpire >= NOW()';
	const SQL_LOGIN = 'SELECT id, active, password FROM `%s` WHERE login = :login';
	const SQL_SET_REFRESH_TOKEN = 'UPDATE `%s` SET refreshToken = :token, refreshExpire = :expire WHERE id = :id';

	/** User table fields to load into AUTH data on login
	 * @var string */
	protected $fields = '*';
	/** PDO instance ID
	 * @var string */
	protected $pdo = 'master';
	/** Auth SQL table
	 * @var string */
	protected $tableAuth = 'sys_auth';
	/** Users SQL table
	 * @var string */
	protected $tableUsers = 'users';

	/**
	 * PdoProvider constructor.
	 * @param string $pdo PDO instance ID, default to "master"
	 * @param string $tableAuth
	 * @param string $tableUsers
	 */
	function __construct($pdo='master', $tableAuth='sys_auth', $tableUsers='users') {
		$this->pdo = $pdo;
		$this->tableAuth = $tableAuth;
		$this->tableUsers = $tableUsers;
		sys::trace(LOG_DEBUG, T_INFO, '[INIT] pdo: '.$pdo.', table: '.$tableAuth);
		sys::pdo($pdo)->exec(str_replace(['{auth}', '{users}'], [$tableAuth, $tableUsers], self::SQL_INIT));
	}

	function authenticate($login, $password): int {
		$id = $this->checkCredentials($login, $password);
		if($id > 0) {
			return (int) $this->authenticateById($id);
		} else return $id;
	}

	function authenticateById($id): bool {
		$prevTraceFn = sys::traceFn($this->_.'->authenticateById');
		try {
			$data = sys::pdo($this->pdo)->prepare(sprintf(self::SQL_AUTHENTICATE, $this->fields, $this->tableUsers))
				->execute(['id'=>$id])->fetch(\PDO::FETCH_ASSOC);
			if(!is_array($data)) return false;
			$AUTH = sys::auth();
			foreach ($data as $k=>$v)
				$AUTH->set($k, $v);
			$AUTH->set('UID', $id);
			return true;
		} catch (\Exception $Ex) {
			return false;
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	function checkCredentials($login, $password): int {
		$prevTraceFn = sys::traceFn($this->_.'->checkCredentials');
		try {
			$data = sys::pdo($this->pdo)->prepare(sprintf(self::SQL_LOGIN, $this->tableAuth))
				->execute(['login'=>$login])->fetch(\PDO::FETCH_ASSOC);
			if(!$data) return AUTH::LOGIN_UNKNOWN;
			if((int)$data['active'] != 1) return AUTH::LOGIN_DISABLED;
			if(!password_verify($password, $data['password'])) return AUTH::LOGIN_PWD_MISMATCH;
			return $data['id'];
		} catch (\Exception $Ex) {
			return AUTH::LOGIN_EXCEPTION;
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	function checkRefreshToken($userId, $token): bool {
		return (bool) sys::pdo($this->pdo)->prepare(sprintf(self::SQL_CHECK_REFRESH_TOKEN, $this->tableAuth))
			->execute(['id'=>$userId, 'token'=>$token])->fetchColumn();
	}

	function setRefreshToken($userId, $token, $expireTime) {
		sys::pdo($this->pdo)->prepare(sprintf(self::SQL_SET_REFRESH_TOKEN, $this->tableAuth))
			->execute(['id'=>$userId, 'token'=>$token, 'expire'=>strftime('%F %T', $expireTime)]);
	}
}
