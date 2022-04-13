<?php
namespace renovant\core\authz;
use const renovant\core\trace\T_INFO;
use renovant\core\sys,
	renovant\core\authz\orm\Def,
	renovant\core\db\PDOStatement,
	renovant\core\util\validator\Validator;

class AuthzManager {
	use \renovant\core\CoreTrait;

	const CACHE_PREFIX	= 'sys.authz.';

	const SQL_DEF_INSERT = 'INSERT INTO %s (type, code, label, query) VALUES (:type, :code, :label, :query)';
	const SQL_DEF_UPDATE = 'UPDATE `%s` SET type = :type, code = :code, label = :label, query = :query WHERE id = :id';
	const SQL_DEF_DELETE = 'DELETE FROM `%s` WHERE id = :id';

	const SQL_FETCH_ROLE_ID			= 'SELECT id FROM %table% WHERE type = "ROLE" AND code = :code';
	const SQL_FETCH_PERMISSION_ID	= 'SELECT id FROM %table% WHERE type = "PERMISSION" AND code = :code';
	const SQL_FETCH_ACL_ID			= 'SELECT id FROM %table% WHERE type = "ACL" AND code = :code';
	const SQL_FETCH_ACL_DATA		= 'SELECT data FROM %table%_maps WHERE user_id = :user_id AND authz_id = :authz_id';

	const SQL_SET_ROLE			= 'INSERT IGNORE INTO %table%_maps (type, user_id, authz_id) VALUES ("USER_ROLE", :user_id, :authz_id)';
	const SQL_SET_PERMISSION	= 'INSERT IGNORE INTO %table%_maps (type, user_id, authz_id) VALUES ("USER_PERMISSION", :user_id, :authz_id)';
	const SQL_SET_ACL			= 'INSERT INTO %table%_maps (type, user_id, authz_id, data) VALUES ("USER_ACL", :user_id, :authz_id, :data) ON DUPLICATE KEY UPDATE data = :data';

	const SQL_REVOKE_ROLE		= 'DELETE FROM %table%_maps WHERE type = "USER_ROLE" AND user_id = :user_id AND authz_id = :authz_id';
	const SQL_REVOKE_PERMISSION	= 'DELETE FROM %table%_maps WHERE type = "USER_PERMISSION" AND user_id = :user_id AND authz_id = :authz_id';
	const SQL_REVOKE_ACL		= 'UPDATE %table%_maps SET data = :data WHERE type = "USER_ACL" AND user_id = :user_id AND authz_id = :authz_id';

	/** Cache ID */
	protected string $cache = 'sys';
	/** Cache entry prefix */
	protected string $cachePrefix = self::CACHE_PREFIX;
	/** PDO instance ID */
	protected string $pdo;
	/** DB tables
	 * @var array */
	protected $tables = [
		'authz'	=> 'sys_authz',
		'users'	=> 'sys_users'
	];

	/**
	 * @param string|null $pdo PDO instance ID
	 * @param array|null $tables
	 */
	function __construct(?string $pdo=null, array $tables=null) {
		$prevTraceFn = sys::traceFn($this->_);
		try {
			$this->pdo = $pdo;
			if ($tables) $this->tables = array_merge($this->tables, $tables);
			sys::trace(LOG_DEBUG, T_INFO, 'initialize AUTHZ storage');
			$PDO = sys::pdo($this->pdo);
			$driver = $PDO->getAttribute(\PDO::ATTR_DRIVER_NAME);
			$PDO->exec(str_replace(
				['t_authz', 't_users'],
				[$this->tables['authz'], $this->tables['users']],
				file_get_contents(__DIR__ . '/sql/init-' . $driver . '.sql')
			));
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	/**
	 * @throws \ReflectionException|AuthzException
	 */
	function createDef(array $data): bool {
		$prevTraceFn = sys::traceFn($this->_);
		try {
			$Def = new Def($data);
			$errors = Validator::validate($Def);
			if(empty($errors)) return (bool) sys::pdo($this->pdo)->prepare(sprintf(self::SQL_DEF_INSERT, $this->tables['authz']))
				->execute([ 'type'=>$Def->type, 'code'=>$Def->code, 'label'=>$Def->label, 'query'=>$Def->query ])->rowCount();
			else throw new AuthzException(500, [implode(', ',array_keys($errors))], $errors);
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	/**
	 * @throws \ReflectionException|AuthzException
	 */
	function updateDef(array $data): bool {
		$prevTraceFn = sys::traceFn($this->_);
		try {
			$Def = new Def($data);
			$errors = Validator::validate($Def);
			if(empty($errors)) return (bool) sys::pdo($this->pdo)->prepare(sprintf(self::SQL_DEF_UPDATE, $this->tables['authz']))
				->execute([ 'id'=>$Def->id, 'type'=>$Def->type, 'code'=>$Def->code, 'label'=>$Def->label, 'query'=>$Def->query ])->rowCount();
			else throw new AuthzException(500, [implode(', ',array_keys($errors))], $errors);
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	function deleteDef(int $id): bool {
		$prevTraceFn = sys::traceFn($this->_);
		try {
			return (bool) sys::pdo($this->pdo)->prepare(sprintf(self::SQL_DEF_DELETE, $this->tables['authz']))
				->execute([ 'id'=>$id ])->rowCount();
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	/** @throws AuthzException */
	function setRole(string $role, int $userId): bool {
		$prevTraceFn = sys::traceFn($this->_);
		try {
			return $this->updateRBAC($role, $userId, 611, self::SQL_FETCH_ROLE_ID, self::SQL_SET_ROLE);
		} finally { sys::traceFn($prevTraceFn); }
	}

	/** @throws AuthzException */
	function revokeRole(string $role, int $userId): bool {
		$prevTraceFn = sys::traceFn($this->_);
		try {
			return $this->updateRBAC($role, $userId, 621, self::SQL_FETCH_ROLE_ID, self::SQL_REVOKE_ROLE);
		} finally { sys::traceFn($prevTraceFn); }
	}

	/** @throws AuthzException */
	function setPermission(string $permission, int $userId): bool {
		$prevTraceFn = sys::traceFn($this->_);
		try {
			return $this->updateRBAC($permission, $userId, 612, self::SQL_FETCH_PERMISSION_ID, self::SQL_SET_PERMISSION);
		} finally { sys::traceFn($prevTraceFn); }

	}

	/** @throws AuthzException */
	function revokePermission(string $permission, int $userId): bool {
		$prevTraceFn = sys::traceFn($this->_);
		try {
			return $this->updateRBAC($permission, $userId, 622, self::SQL_FETCH_PERMISSION_ID, self::SQL_REVOKE_PERMISSION);
		} finally { sys::traceFn($prevTraceFn); }

	}

	/** @throws AuthzException */
	function setAcl(string $acl, int $userId, int $dataId): bool {
		if(!$authzId = $this->pdo(self::SQL_FETCH_ACL_ID)->execute(['code'=>$acl])->fetchColumn())
			throw new AuthzException(613, [$acl]);
		$data = $this->pdo(self::SQL_FETCH_ACL_DATA)->execute(['user_id'=>$userId, 'authz_id'=>$authzId])->fetchColumn();
		if($data) $data = explode(',', $data);
		$data[] = (string)$dataId;
		$data = array_unique($data, SORT_NUMERIC);
		return (bool) $this->pdo(self::SQL_SET_ACL)->execute(['user_id'=>$userId, 'authz_id'=>$authzId, 'data'=>implode(',', $data)])->rowCount();
	}

	/** @throws AuthzException */
	function revokeAcl(string $acl, int $userId, int $dataId): bool {
		if(!$authzId = $this->pdo(self::SQL_FETCH_ACL_ID)->execute(['code'=>$acl])->fetchColumn())
			throw new AuthzException(623, [$acl]);
		$data = $this->pdo(self::SQL_FETCH_ACL_DATA)->execute(['user_id'=>$userId, 'authz_id'=>$authzId])->fetchColumn();
		if($data) $data = explode(',', $data);
		$data = array_diff($data, [$dataId]);
		$data = array_unique($data, SORT_NUMERIC);
		return (bool) $this->pdo(self::SQL_REVOKE_ACL)->execute(['user_id'=>$userId, 'authz_id'=>$authzId, 'data'=>implode(',', $data)])->rowCount();
	}

	/** @throws AuthzException */
	protected function updateRBAC($code, $userId, $exCode, $sqlFetch, $sqlUpdate) {
		if(!$authzId = $this->pdo($sqlFetch)->execute(['code'=>$code])->fetchColumn())
			throw new AuthzException($exCode, [$code]);
		return (bool) $this->pdo($sqlUpdate)->execute([ 'authz_id'=>$authzId, 'user_id'=>$userId ])->rowCount();

	}
	protected function pdo($sql): PDOStatement {
		return sys::pdo($this->pdo)->prepare(str_replace('%table%', $this->tables['authz'], $sql));
	}
}
