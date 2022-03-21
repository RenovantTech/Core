<?php
namespace renovant\core\auth\provider;
use const renovant\core\trace\T_INFO;
use renovant\core\sys,
	renovant\core\auth\AuthException,
	renovant\core\auth\AuthService,
	renovant\core\util\crypto\Crypto;
class PdoProvider implements ProviderInterface {
	use \renovant\core\CoreTrait;

	const SQL_AUTHENTICATE = 'SELECT %s FROM %s WHERE id = :id';
	const SQL_CHECK_PWD = 'SELECT %s FROM %s WHERE user_id = :user_id';

	const SQL_TOKEN_CHECK		= 'SELECT COUNT(*) FROM `%s` WHERE type = :type AND user_id = :user_id AND token = :token AND expire >= NOW()';
	const SQL_TOKEN_FETCH		= 'SELECT user_id, data FROM `%s` WHERE type = :type AND token = :token AND expire >= NOW()';
	const SQL_TOKEN_SET			= 'INSERT INTO `%s` (type, user_id, token, data, expire) VALUES (:type, :user_id, :token, :data, FROM_UNIXTIME(:expire))';
	const SQL_TOKEN_DELETE		= 'DELETE FROM `%s` WHERE type = :type AND user_id = :user_id AND token = :token';

	const SQL_2FA_DISABLE		= 'UPDATE `%s` SET tfaKey = NULL, tfaRescue = NULL WHERE user_id = :user_id';
	const SQL_2FA_FETCH			= 'SELECT tfaKey, tfaRescue FROM `%s` WHERE user_id = :user_id';
	const SQL_2FA_IS_ENABLED	= 'SELECT COUNT(*) FROM `%s` WHERE user_id = :user_id AND tfaKey IS NOT NULL';
	const SQL_2FA_SET			= 'UPDATE `%s` SET tfaKey = :tfaKey, tfaRescue = :tfaRescue WHERE user_id = :user_id';

	const SQL_LOGIN = 'SELECT user_id, active, password, tfaKey, tfaRescue FROM `%s` WHERE login = :login';
	const SQL_SET_ACTIVE = 'UPDATE `%s` SET active = :active WHERE user_id = :user_id';
	const SQL_SET_EMAIL = 'UPDATE `%s` SET email = :email WHERE id = :user_id';
	const SQL_SET_PASSWORD = 'UPDATE `%s` SET password = :password, passwordExpire = FROM_UNIXTIME(:expire) WHERE user_id = :user_id';

	/** User table fields to load into AUTH data on login
	 * @var string */
	protected $fields = '*';
	/** PDO instance ID
	 * @var string */
	protected $pdo;
	/** DB tables
	 * @var array */
	protected $tables = [
		'auth'		=> 'sys_users_auth',
		'tokens'	=> 'sys_users_tokens',
		'users'		=> 'sys_users'
	];

	/**
	 * PdoProvider constructor.
	 * @param string|null $pdo PDO instance ID
	 * @param array|null $tables
	 */
	function __construct(?string $pdo=null, array $tables=null) {
		$prevTraceFn = sys::traceFn('sys.AuthProvider');
		$this->pdo = $pdo;
		if($tables) $this->tables = array_merge($this->tables, $tables);
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
	 * @throws \SodiumException
	 */
	function fetchCredentials(string $login): array {
		$data = sys::pdo($this->pdo)->prepare(sprintf(self::SQL_LOGIN, $this->tables['auth']))
			->execute(['login'=>$login])->fetch(\PDO::FETCH_ASSOC);
		if(is_array($data)) {
			$data['tfaKey'] = is_null($data['tfaKey']) ? null : Crypto::decrypt($data['tfaKey']);
			$data['tfaRescue'] = is_null($data['tfaRescue']) ? null : Crypto::decrypt($data['tfaRescue']);
		}
		return $data;
	}

	function disable2FA(int $userID): bool {
		return (bool) sys::pdo($this->pdo)->prepare(sprintf(self::SQL_2FA_DISABLE, $this->tables['auth']))
			->execute(['user_id'=>$userID])->rowCount();
	}

	function isEnabled2FA(int $userID): bool {
		return (bool) sys::pdo($this->pdo)->prepare(sprintf(self::SQL_2FA_IS_ENABLED, $this->tables['auth']))
			->execute(['user_id'=>$userID])->fetchColumn();
	}

	/** @throws AuthException */
	function fetchUserData(int $id): array {
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
		}
	}

	/** @throws \SodiumException */
	function fetch2FA(int $userID): array {
		$data = sys::pdo($this->pdo)->prepare(sprintf(self::SQL_2FA_FETCH,$this->tables['auth']))
			->execute(['user_id'=>$userID])->fetch(\PDO::FETCH_ASSOC);
		$data['tfaKey'] = is_null($data['tfaKey']) ? null : Crypto::decrypt($data['tfaKey']);
		$data['tfaRescue'] = is_null($data['tfaRescue']) ? null : Crypto::decrypt($data['tfaRescue']);
		return $data;
	}

	function setActive(int $userID, bool $active): int {
		return sys::pdo($this->pdo)->prepare(sprintf(self::SQL_SET_ACTIVE, $this->tables['auth']))
			->execute(['user_id'=>$userID, 'active'=>(int)$active])->rowCount();
	}

	function setEmail(int $userID, string $email): int {
		return sys::pdo($this->pdo)->prepare(sprintf(self::SQL_SET_EMAIL, $this->tables['users']))
			->execute(['user_id'=>$userID, 'email'=>$email])->rowCount();
	}

	function setPassword(int $userID, string $pwd, ?int $expireTime=null, ?string $oldPwd=null): int {
		try {
			if($oldPwd) {
				$storedPwd = sys::pdo($this->pdo)->prepare(sprintf(self::SQL_CHECK_PWD, 'password', $this->tables['auth']))
					->execute(['user_id'=>$userID])->fetchColumn();
				if(!password_verify($oldPwd, $storedPwd)) return AuthService::SET_PWD_MISMATCH;
			}
			return sys::pdo($this->pdo)->prepare(sprintf(self::SQL_SET_PASSWORD, $this->tables['auth']))
				->execute(['user_id'=>$userID, 'password'=>password_hash($pwd, PASSWORD_DEFAULT), 'expire'=>$expireTime])->rowCount();
		} catch (\Exception $Ex) {
			return AuthService::SET_PWD_EXCEPTION;
		}
	}

	/** @throws \SodiumException */
	function set2FA(int $userID, string $secretKey, array $rescueCodes): bool {
		return (bool) sys::pdo($this->pdo)->prepare(sprintf(self::SQL_2FA_SET, $this->tables['auth']))
			->execute(['user_id'=>$userID, 'tfaKey'=>Crypto::encrypt($secretKey), 'tfaRescue'=>Crypto::encrypt($rescueCodes)])->rowCount();
	}

	function tokenCheck(string $tokenType, string $token, int $userID): bool {
		return (bool) sys::pdo($this->pdo)->prepare(sprintf(self::SQL_TOKEN_CHECK, $this->tables['tokens']))
			->execute(['type'=>$tokenType, 'user_id'=>$userID, 'token'=>$token])->fetchColumn();
	}

	function tokenDelete(string $tokenType, string $token, int $userID): bool {
		return (bool) sys::pdo($this->pdo)->prepare(sprintf(self::SQL_TOKEN_DELETE, $this->tables['tokens']))
			->execute(['type' => $tokenType, 'user_id'=>$userID, 'token'=>$token])->rowCount();
	}

	function tokenFetch(string $tokenType, string $token): ?array {
		$data = sys::pdo($this->pdo)->prepare(sprintf(self::SQL_TOKEN_FETCH, $this->tables['tokens']))
			->execute(['type' => $tokenType, 'token' => $token])->fetch(\PDO::FETCH_NUM);
		if (!is_array($data)) return null;
		return $data;
	}

	function tokenSet(string $tokenType, int $userID, string $token, ?string $data, int $expireTime) {
		sys::pdo($this->pdo)->prepare(sprintf(self::SQL_TOKEN_SET, $this->tables['tokens']))
			->execute(['type'=>$tokenType, 'user_id'=>$userID, 'token'=>$token, 'data'=>$data, 'expire'=>$expireTime]);
	}
}
