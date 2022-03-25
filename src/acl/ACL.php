<?php
namespace renovant\core\acl;

class ACL {

	/** singleton instance */
	static private $_ACL;

	/** ACL actions */
	protected array $actions = [];
	/** ACL filters */
	protected array $filters = [];
	/** ACL roles */
	protected array $roles = [];

	/**
	 * @throws AclException
	 */
	static function init(array $actions, array $filters, array $roles): ACL {
		if(self::$_ACL) throw new AclException(1);
		return self::$_ACL = new ACL($actions, $filters, $roles);
	}

	static function instance(): ACL {
		return self::$_ACL;
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
