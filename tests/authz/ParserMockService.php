<?php
namespace test\authz;
/**
 * @authz-role(mock.role1)
 * @authz-permission(mock.perm1)
 */
class ParserMockService  {
	use \renovant\core\authz\AuthzTrait;

	/**
	 * @authz-roles-any(foo.role2, foo.role3)
	 * @authz-permissions-any(foo.perm1, foo.perm2)
	 */
	function foo() {
		return 'foo';
	}

	/**
	 * @authz-roles-all(bar.role2, bar.role3)
	 */
	function bar() {
		return 'bar';
	}

	/**
	 * @authz-acl(reboot="$id")
	 * @param $region
	 * @param $id
	 * @return bool
	 */
	function reboot($region, $id) {
		return true;
	}


	/**
	 * @authz-acl-all(area="$area", district="$district" )
	 * @param $area
	 * @param $district
	 * @return bool
	 */
	function query($area, $district) {
		return true;
	}
}
