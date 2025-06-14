<?php
namespace test\authz;

use renovant\core\authz\ObjAuthzInterface;

/**
 * @authz-allow-roles(super-admin, sys-admin)
 * @authz-role(role.service)
 */
class ObjAuthzMock implements ObjAuthzInterface {
	use \renovant\core\CoreTrait;

	/**
	 * @authz-allow-roles(role.service.foo)
	 */
	public function allowRoles() {
		return 'allow-roles';
	}

	/**
	 * @authz-role(role.service.foo)
	 */
	public function role() {
		return 'role';
	}

	/**
	 * @authz-roles-all(role.service.foo, role.service.bar )
	 */
	public function rolesAll() {
		return 'roles-all';
	}

	/**
	 * @authz-roles-any(role.service.foo, role.service.bar )
	 */
	public function rolesAny() {
		return 'roles-any';
	}

	/**
	 * @authz-permission(perm.service.foo)
	 */
	public function permission() {
		return 'permission';
	}

	/**
	 * @authz-permissions-all(perm.service.foo, perm.service.bar )
	 */
	public function permissionsAll() {
		return 'permissions-all';
	}

	/**
	 * @authz-permissions-any(perm.service.foo, perm.service.bar )
	 */
	public function permissionsAny() {
		return 'permissions-any';
	}

	/**
	 * @authz-acl(acl.foo="$id")
	 */
	public function acl(string $area, string $district, int $id) {
		return 'acl-' . $area . '-' . $district . '-' . $id;
	}

	/**
	 * @authz-acl-all(acl.area="$area", acl.district="$district" )
	 */
	public function aclAll(string $area, string $district, int $id) {
		return 'acl-all-' . $area . '-' . $district . '-' . $id;
	}

	/**
	 * @authz-acl-any(acl.area="$area", acl.district="$district" )
	 */
	public function aclAny(string $area, string $district, int $id) {
		return 'acl-any-' . $area . '-' . $district . '-' . $id;
	}
}
