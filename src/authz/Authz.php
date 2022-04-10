<?php
namespace renovant\core\authz;

use phpDocumentor\Reflection\DocBlock\Tags\BaseTag;
class Authz {

	/** singleton instance */
	static private $_Authz;

	/** AUTHZ actions */
	protected array $actions = [];
	/** AUTHZ filters */
	protected array $filters = [];
	/** AUTHZ roles */
	protected array $roles = [];
	/** AUTHZ permissions */
	protected array $permissions = [];

	protected int $verified = 0;

	/**
	 * @throws AuthzException
	 */
	static function init(array $actions, array $filters, array $roles, array $permissions): Authz {
		if(self::$_Authz) throw new AuthzException(1);
		return self::$_Authz = new Authz($actions, $filters, $roles, $permissions);
	}

	static function instance(): Authz {
		return self::$_Authz;
	}

	private function __construct(array $actions, array $filters, array $roles, array $permissions) {
		$this->actions = $actions;
		$this->filters = $filters;
		$this->roles = $roles;
		$this->permissions = $permissions;
	}

	/**
	 * @param string $code
	 * @return bool
	 */
	function action(string $code): bool {
		if(in_array($code, $this->actions)) {
			$this->verified = 1; return true;
		} else {
			$this->verified = 2; return false;
		}
	}

	/**
	 * @param string $code
	 * @return bool
	 */
	function filter(string $code): bool {
		if(isset($this->filters[$code])) {
			$this->verified = 1; return true;
		} else {
			$this->verified = 2; return false;
		}
	}

	/**
	 * @param string $role
	 * @return bool
	 */
	function role(string $role): bool {
		if(in_array($role, $this->roles)) {
			$this->verified = 1; return true;
		} else {
			$this->verified = 2; return false;
		}
	}

	/**
	 * @param string $permission
	 * @return bool
	 */
	function permissions(string $permission): bool {
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
			'ACTIONS' => $this->actions,
			'FILTERS' => $this->filters,
			'ROLES' => $this->roles,
			'PERMISSIONS' => $this->permissions
		];
	}
}
