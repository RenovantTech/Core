<?php
namespace renovant\core\authz;

class Authz {

	/** singleton instance */
	static private $_Authz;

	/** AUTHZ actions */
	protected array $actions = [];
	/** AUTHZ filters */
	protected array $filters = [];
	/** AUTHZ roles */
	protected array $roles = [];

	/**
	 * @throws AuthzException
	 */
	static function init(array $actions, array $filters, array $roles): Authz {
		if(self::$_Authz) throw new AuthzException(1);
		return self::$_Authz = new Authz($actions, $filters, $roles);
	}

	static function instance(): Authz {
		return self::$_Authz;
	}

	private function __construct(array $actions, array $filters, array $roles) {
		$this->actions = $actions;
		$this->filters = $filters;
		$this->roles = $roles;
	}

	/**
	 * @param string $code
	 * @return bool
	 */
	function action(string $code): bool {
		return in_array($code, $this->actions);
	}

	/**
	 * @param string $code
	 * @return bool
	 */
	function filter(string $code): bool {
		return isset($this->filters[$code]);
	}

	/**
	 * @param string $role
	 * @return bool
	 */
	function role(string $role): bool {
		return in_array($role, $this->roles);
	}
}
