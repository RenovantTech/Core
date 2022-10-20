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

	const SQL_DEF_INSERT = 'INSERT INTO %table% (type, code, label, config) VALUES (:type, :code, :label, :config) ON DUPLICATE KEY UPDATE label = :label, config = :config';
	const SQL_DEF_DELETE = 'DELETE FROM %table% WHERE id = :id';
	const SQL_DEF_RENAME = 'UPDATE %table% SET code = :code WHERE id = :id';

	const SQL_FETCH_ID			= 'SELECT id FROM %table% WHERE type = :type AND code = :code';
	const SQL_FETCH_CONFIG		= 'SELECT config FROM %table% WHERE type = :type AND code = :code';
	const SQL_FETCH_ACL_DATA	= 'SELECT data FROM %table%_maps WHERE user_id = :user_id AND authz_id = :authz_id';

	const SQL_SET_ROLE			= 'INSERT IGNORE INTO %table%_maps (type, user_id, authz_id) VALUES ("USER_ROLE", :user_id, :authz_id)';
	const SQL_SET_PERMISSION	= 'INSERT IGNORE INTO %table%_maps (type, user_id, authz_id) VALUES ("USER_PERMISSION", :user_id, :authz_id)';
	const SQL_SET_ACL			= 'INSERT INTO %table%_maps (type, user_id, authz_id, data) VALUES ("USER_ACL", :user_id, :authz_id, :data) ON DUPLICATE KEY UPDATE data = :data';

	const SQL_REVOKE_ROLE		= 'DELETE FROM %table%_maps WHERE type = "USER_ROLE" AND user_id = :user_id AND authz_id = :authz_id';
	const SQL_REVOKE_PERMISSION	= 'DELETE FROM %table%_maps WHERE type = "USER_PERMISSION" AND user_id = :user_id AND authz_id = :authz_id';
	const SQL_REVOKE_ACL		= 'DELETE FROM %table%_maps WHERE type = "USER_ACL" AND user_id = :user_id AND authz_id = :authz_id';

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

	/** @throws \ReflectionException|AuthzException */
	function defineRole(string $role, $description): bool {
		$prevTraceFn = sys::traceFn($this->_);
		try {
			return $this->_define(new Def(['type' => Authz::TYPE_ROLE, 'code' => $role, 'label' => $description]));
		} finally { sys::traceFn($prevTraceFn); }
	}

	/** @throws \ReflectionException|AuthzException */
	function definePermission(string $permission, $description): bool {
		$prevTraceFn = sys::traceFn($this->_);
		try {
			return $this->_define(new Def(['type'=>Authz::TYPE_PERMISSION, 'code'=>$permission, 'label'=>$description]));
		} finally { sys::traceFn($prevTraceFn); }
	}

	/** @throws \ReflectionException|AuthzException */
	function defineAcl(string $acl, $description, $queryBase, $filterQuery, $filterValues): bool {
		$prevTraceFn = sys::traceFn($this->_);
		try {
			return $this->_define(new Def(['type'=>Authz::TYPE_ACL, 'code'=>$acl, 'label'=>$description, 'config'=>json_encode([
				'queryBase' => $queryBase,
				'filterQuery' => $filterQuery,
				'filterValues' => $filterValues
			]) ]));
		} finally { sys::traceFn($prevTraceFn); }
	}

	/** @throws \ReflectionException|AuthzException */
	protected function _define(Def $Def): bool {
		$errors = Validator::validate($Def);
		if(empty($errors)) return (bool) $this->pdo(self::SQL_DEF_INSERT)->execute([
			'type'=>$Def->type,
			'code'=>$Def->code,
			'label'=>$Def->label,
			'config'=>$Def->config
		])->rowCount();
		else throw new AuthzException(500, [implode(', ',array_keys($errors))], $errors);
	}

	/** @throws AuthzException */
	function fetchAclConfig(string $code): array {
		$prevTraceFn = sys::traceFn($this->_);
		try {
			if(!$config = $this->pdo(self::SQL_FETCH_CONFIG)->execute(['type'=>Authz::TYPE_ACL, 'code'=>$code])->fetchColumn())
				throw new AuthzException(501, [Authz::TYPE_ACL, $code]);
			return (array) json_decode($config);
		} finally { sys::traceFn($prevTraceFn); }
	}

	/** @throws AuthzException */
	function delete(string $type, string $code): bool {
		$prevTraceFn = sys::traceFn($this->_);
		try {
			if(!$authzId = $this->pdo(self::SQL_FETCH_ID)->execute(['type'=>$type, 'code'=>$code])->fetchColumn())
				throw new AuthzException(501, [$type, $code]);
			return (bool) $this->pdo(self::SQL_DEF_DELETE)->execute(['id'=>$authzId])->rowCount();
		} finally { sys::traceFn($prevTraceFn); }
	}

	/** @throws \ReflectionException|AuthzException */
	function rename(string $type, string $code, $newCode): bool {
		$prevTraceFn = sys::traceFn($this->_);
		try {
			if(!$authzId = $this->pdo(self::SQL_FETCH_ID)->execute(['type'=>$type, 'code'=>$code])->fetchColumn())
				throw new AuthzException(502, [$type, $code]);
			$Def = new Def(['type'=>$type, 'code'=>$newCode]);
			$errors = Validator::validate($Def);
			if(empty($errors)) return (bool) $this->pdo(self::SQL_DEF_RENAME)->execute(['id'=>$authzId, 'code'=>$newCode])->rowCount();
			else throw new AuthzException(500, [implode(', ',array_keys($errors))], $errors);
		} finally { sys::traceFn($prevTraceFn); }
	}

	/** @throws AuthzException */
	function setUserRole(string $role, int $userId): bool {
		$prevTraceFn = sys::traceFn($this->_);
		try {
			if(!$authzId = $this->pdo(self::SQL_FETCH_ID)->execute(['type'=>Authz::TYPE_ROLE, 'code'=>$role])->fetchColumn())
				throw new AuthzException(611, [$role]);
			return (bool) $this->pdo(self::SQL_SET_ROLE)->execute(['authz_id'=>$authzId, 'user_id'=>$userId])->rowCount();
		} finally {
			sys::cache($this->cache)->delete($this->cachePrefix.$userId);
			sys::traceFn($prevTraceFn);
		}
	}

	/** @throws AuthzException */
	function revokeUserRole(string $role, int $userId): bool {
		$prevTraceFn = sys::traceFn($this->_);
		try {
			if(!$authzId = $this->pdo(self::SQL_FETCH_ID)->execute(['type'=>Authz::TYPE_ROLE, 'code'=>$role])->fetchColumn())
				throw new AuthzException(631, [$role]);
			return (bool) $this->pdo(self::SQL_REVOKE_ROLE)->execute(['authz_id'=>$authzId, 'user_id'=>$userId])->rowCount();
		} finally {
			sys::cache($this->cache)->delete($this->cachePrefix.$userId);
			sys::traceFn($prevTraceFn);
		}
	}

	/** @throws AuthzException */
	function setUserPermission(string $permission, int $userId): bool {
		$prevTraceFn = sys::traceFn($this->_);
		try {
			if(!$authzId = $this->pdo(self::SQL_FETCH_ID)->execute(['type'=>Authz::TYPE_PERMISSION, 'code'=>$permission])->fetchColumn())
				throw new AuthzException(612, [$permission]);
			return (bool) $this->pdo(self::SQL_SET_PERMISSION)->execute(['authz_id'=>$authzId, 'user_id'=>$userId])->rowCount();
		} finally {
			sys::cache($this->cache)->delete($this->cachePrefix.$userId);
			sys::traceFn($prevTraceFn);
		}
	}

	/** @throws AuthzException */
	function revokeUserPermission(string $permission, int $userId): bool {
		$prevTraceFn = sys::traceFn($this->_);
		try {
			if(!$authzId = $this->pdo(self::SQL_FETCH_ID)->execute(['type'=>Authz::TYPE_PERMISSION, 'code'=>$permission])->fetchColumn())
				throw new AuthzException(632, [$permission]);
			return (bool) $this->pdo(self::SQL_REVOKE_PERMISSION)->execute(['authz_id'=>$authzId, 'user_id'=>$userId])->rowCount();
		} finally {
			sys::cache($this->cache)->delete($this->cachePrefix.$userId);
			sys::traceFn($prevTraceFn);
		}
	}

	/** @throws AuthzException */
	function setUserAcl(string $acl, int $userId, array $items): bool {
		$prevTraceFn = sys::traceFn($this->_);
		try {
			if(!$authzId = $this->pdo(self::SQL_FETCH_ID)->execute(['type'=>Authz::TYPE_ACL, 'code'=>$acl])->fetchColumn())
				throw new AuthzException(613, [$acl]);
			$items = array_unique($items, SORT_NUMERIC);
			return (bool) $this->pdo(self::SQL_SET_ACL)->execute(['user_id'=>$userId, 'authz_id'=>$authzId, 'data'=>json_encode($items)])->rowCount();
		} finally {
			sys::cache($this->cache)->delete($this->cachePrefix.$userId);
			sys::traceFn($prevTraceFn);
		}
	}

	/** @throws AuthzException */
	function setUserAclItem(string $acl, int $userId, int|string $item): bool {
		$prevTraceFn = sys::traceFn($this->_);
		try {
			if(!$authzId = $this->pdo(self::SQL_FETCH_ID)->execute(['type'=>Authz::TYPE_ACL, 'code'=>$acl])->fetchColumn())
				throw new AuthzException(613, [$acl]);
			$data = $this->pdo(self::SQL_FETCH_ACL_DATA)->execute(['user_id'=>$userId, 'authz_id'=>$authzId])->fetchColumn();
			$data = ($data) ? (array)json_decode($data) : [];
			$data[] = $item;
			$data = array_unique($data, SORT_NUMERIC);
			return (bool) $this->pdo(self::SQL_SET_ACL)->execute(['user_id'=>$userId, 'authz_id'=>$authzId, 'data'=>json_encode($data)])->rowCount();
		} finally {
			sys::cache($this->cache)->delete($this->cachePrefix.$userId);
			sys::traceFn($prevTraceFn);
		}
	}

	/** @throws AuthzException */
	function fetchUserAclData(string $acl, int $userId): array {
		$prevTraceFn = sys::traceFn($this->_);
		try {
			if(!$authzId = $this->pdo(self::SQL_FETCH_ID)->execute(['type'=>Authz::TYPE_ACL, 'code'=>$acl])->fetchColumn())
				throw new AuthzException(623, [$acl]);
			$data = $this->pdo(self::SQL_FETCH_ACL_DATA)->execute(['user_id'=>$userId, 'authz_id'=>$authzId])->fetchColumn();
			return ($data) ? (array)json_decode($data) : [];
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	/** @throws AuthzException */
	function revokeUserAcl(string $acl, int $userId): bool {
		$prevTraceFn = sys::traceFn($this->_);
		try {
			if(!$authzId = $this->pdo(self::SQL_FETCH_ID)->execute(['type'=>Authz::TYPE_ACL, 'code'=>$acl])->fetchColumn())
				throw new AuthzException(633, [$acl]);
			return (bool) $this->pdo(self::SQL_REVOKE_ACL)->execute(['user_id'=>$userId, 'authz_id'=>$authzId])->rowCount();
		} finally {
			sys::cache($this->cache)->delete($this->cachePrefix.$userId);
			sys::traceFn($prevTraceFn);
		}
	}

	/** @throws AuthzException */
	function revokeUserAclItem(string $acl, int $userId, int|string $item): bool {
		$prevTraceFn = sys::traceFn($this->_);
		try {
			if(!$authzId = $this->pdo(self::SQL_FETCH_ID)->execute(['type'=>Authz::TYPE_ACL, 'code'=>$acl])->fetchColumn())
				throw new AuthzException(633, [$acl]);
			$data = $this->pdo(self::SQL_FETCH_ACL_DATA)->execute(['user_id'=>$userId, 'authz_id'=>$authzId])->fetchColumn();
			$data = ($data) ? (array)json_decode($data) : [];
			$data = array_diff($data, [$item]);
			$data = array_unique($data, SORT_NUMERIC);
			if(empty($data))
				return (bool) $this->pdo(self::SQL_REVOKE_ACL)->execute(['user_id'=>$userId, 'authz_id'=>$authzId])->rowCount();
			else
				return (bool) $this->pdo(self::SQL_SET_ACL)->execute(['user_id'=>$userId, 'authz_id'=>$authzId, 'data'=>json_encode($data)])->rowCount();
		} finally {
			sys::cache($this->cache)->delete($this->cachePrefix.$userId);
			sys::traceFn($prevTraceFn);
		}
	}

	protected function pdo($sql): PDOStatement {
		return sys::pdo($this->pdo)->prepare(str_replace('%table%', $this->tables['authz'], $sql));
	}
}
