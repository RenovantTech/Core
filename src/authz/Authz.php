<?php
namespace renovant\core\authz;
class Authz {

	const TYPE_ROLE			= 'ROLE';
	const TYPE_PERMISSION	= 'PERMISSION';
	const TYPE_ACL			= 'ACL';

	/** singleton instance */
	static private $_Authz = null;

	/** AUTHZ ACL */
	protected array $acl = [];
	/** AUTHZ roles */
	protected array $roles = [];
	/** AUTHZ permissions */
	protected array $permissions = [];

	protected int $verified = 0;

	/**
	 * @throws AuthzException
	 */
	static function init(array $roles, array $permissions, array $acl): Authz {
		if(self::$_Authz) throw new AuthzException(1);
		return self::$_Authz = new Authz($roles, $permissions, $acl);
	}

	static function instance(): ?Authz {
		return self::$_Authz;
	}

	private function __construct(array $roles, array $permissions, array $acl) {
		$this->acl = $acl;
		$this->roles = $roles;
		$this->permissions = $permissions;
	}

	function acl(string $acl, $val): bool {
		if(isset($this->acl[$acl]) && in_array($val, $this->acl[$acl])) {
			$this->verified = 1; return true;
		} else {
			$this->verified = 2; return false;
		}
	}

	function aclValues(string $acl): ?array {
		if(isset($this->acl[$acl])) {
			$this->verified = 1; return $this->acl[$acl];
		} else {
			$this->verified = 2; return null;
		}
	}

	function role(string $role): bool {
		if(in_array($role, $this->roles)) {
			$this->verified = 1; return true;
		} else {
			$this->verified = 2; return false;
		}
	}

	function permission(string $permission): bool {
		if(in_array($permission, $this->permissions)) {
			$this->verified = 1; return true;
		} else {
			$this->verified = 2; return false;
		}
	}

	function verified(): int {
		return $this->verified;
	}
	function dump(): array {
		return [
			'ROLE' => $this->roles,
			'PERMISSION' => $this->permissions,
			'ACL' => $this->acl
		];
	}
}