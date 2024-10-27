<?php
namespace renovant\core\authz;

class Authz {
	public const TYPE_ROLE       = 'ROLE';
	public const TYPE_PERMISSION = 'PERMISSION';
	public const TYPE_ACL        = 'ACL';

	/** singleton instance */
	private static $_Authz = null;

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
	public static function init(array $roles, array $permissions, array $acl): Authz {
		if (self::$_Authz) {
			throw new AuthzException(1);
		}
		return self::$_Authz = new Authz($roles, $permissions, $acl);
	}

	public static function instance(): ?Authz {
		return self::$_Authz;
	}

	private function __construct(array $roles, array $permissions, array $acl) {
		$this->acl         = $acl;
		$this->roles       = $roles;
		$this->permissions = $permissions;
	}

	public function acl(string $acl, $val): bool {
		if (isset($this->acl[$acl]) && in_array($val, $this->acl[$acl])) {
			$this->verified = 1;
			return true;
		} else {
			$this->verified = 2;
			return false;
		}
	}

	public function aclValues(string $acl): ?array {
		if (isset($this->acl[$acl])) {
			$this->verified = 1;
			return $this->acl[$acl];
		} else {
			$this->verified = 2;
			return null;
		}
	}

	public function role(string $role): bool {
		if (in_array($role, $this->roles)) {
			$this->verified = 1;
			return true;
		} else {
			$this->verified = 2;
			return false;
		}
	}

	public function permission(string $permission): bool {
		if (in_array($permission, $this->permissions)) {
			$this->verified = 1;
			return true;
		} else {
			$this->verified = 2;
			return false;
		}
	}

	public function verified(): int {
		return $this->verified;
	}

	public function dump(): array {
		return [
			'ROLE'       => $this->roles,
			'PERMISSION' => $this->permissions,
			'ACL'        => $this->acl
		];
	}
}
