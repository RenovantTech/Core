<?php
namespace test\authz;
/**
 * @authz(role="mock.role1, mock.role2", action="mock.action1", filter="mock.filter1")
 * @authz-action(mock.action2, mock.action3)
 * @authz-filter(mock.filter2, mock.filter3)
 * @authz-role(mock.role3)
 */
class ParserMockService  {
	use \renovant\core\authz\AuthzTrait;

	/**
	 * @authz(role="role.foo1", action="action.foo1", filter="filter.foo1")
	 * @authz-action(foo.action2, foo.action3)
	 * @authz-filter(foo.filter2, foo.filter3)
	 * @authz-role(foo.role2, foo.role3)
	 */
	function foo() {
		return 'foo';
	}

	/**
	 * @authz(role="role.bar1", action="action.bar1", filter="filter.bar1")
	 * @authz-action(bar.action2, bar.action3)
	 * @authz-filter(bar.filter2, bar.filter3)
	 * @authz-role(bar.role2, bar.role3)
	 */
	function bar() {
		return 'bar';
	}
}
