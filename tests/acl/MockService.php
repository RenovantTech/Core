<?php
namespace test\acl;

/**
 * @acl(role="mock.role1, mock.role2", action="mock.action1", filter="mock.filter1")
 * @acl-action(mock.action2, mock.action3)
 * @acl-filter(mock.filter2, mock.filter3)
 * @acl-role(mock.role3)
 */
class MockService  {
	use \renovant\core\acl\AclTrait;

	protected $Child;

	protected $name;

	/**
	 * @acl(role="role.foo1", action="action.foo1", filter="filter.foo1")
	 * @acl-action(foo.action2, foo.action3)
	 * @acl-filter(foo.filter2, foo.filter3)
	 * @acl-role(foo.role2, foo.role3)
	 */
	function foo() {
	}

	/**
	 * @acl(role="role.bar1", action="action.bar1", filter="filter.bar1")
	 * @acl-action(bar.action2, bar.action3)
	 * @acl-filter(bar.filter2, bar.filter3)
	 * @acl-role(bar.role2, bar.role3)
	 */
	function bar() {
	}
}
