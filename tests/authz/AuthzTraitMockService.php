<?php
namespace test\authz;
/**
 * @authz-role(role.service)
 */
class AuthzTraitMockService {
	use \renovant\core\authz\AuthzTrait;

	/**
	 * @authz-role(role.service.foo)
	 */
	function role() {
		return 'role';
	}

	/**
	 * @authz-roles-all(role.service.foo, role.service.bar )
	 */
	function rolesAll() {
		return 'roles-all';
	}

	/**
	 * @authz-roles-any(role.service.foo, role.service.bar )
	 */
	function rolesAny() {
		return 'roles-any';
	}
}
