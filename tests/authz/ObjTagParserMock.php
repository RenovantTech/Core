<?php
namespace test\authz;
/**
 * @authz-role(mock.role1)
 * @authz-permission(mock.perm1)
 */
class ObjTagParserMock {
	use \renovant\core\CoreTrait;

	/**
	 * @authz-roles-any(foo.role2, foo.role3)
	 * @authz-permissions-any(foo.perm1, foo.perm2)
	 */
	function foo(): string {
		return 'foo';
	}

	/**
	 * @authz-roles-all(bar.role2, bar.role3)
	 */
	function bar(): string {
		return 'bar';
	}

	/**
	 * @authz-acl(reboot="$id")
	 */
	function reboot(string $region, int $id): bool {
		return true;
	}


	/**
	 * @authz-acl-all(area="$area", district="$district" )
	 */
	function query(string $area, string $district): bool {
		return true;
	}
}
