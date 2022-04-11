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

	/**
	 * @authz-permission(perm.service.foo)
	 */
	function permission() {
		return 'permission';
	}

	/**
	 * @authz-permissions-all(perm.service.foo, perm.service.bar )
	 */
	function permissionsAll() {
		return 'permissions-all';
	}

	/**
	 * @authz-permissions-any(perm.service.foo, perm.service.bar )
	 */
	function permissionsAny() {
		return 'permissions-any';
	}

	/**
	 * @authz-acl(acl.foo="$id")
	 */
	function acl($area, $district, $id) {
		return 'acl-'.$area.'-'.$district.'-'.$id;
	}

	/**
	 * @authz-acl-all(acl.area="$area", acl.district="$district" )
	 */
	function aclAll($area, $district, $id) {
		return 'acl-all-'.$area.'-'.$district.'-'.$id;
	}

	/**
	 * @authz-acl-any(acl.area="$area", acl.district="$district" )
	 */
	function aclAny($area, $district, $id) {
		return 'acl-any-'.$area.'-'.$district.'-'.$id;
	}
}
