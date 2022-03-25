<?php
namespace test\acl;

/**
 * @acl-role(role.service2)
 */
class MockService2  {
	use \renovant\core\acl\AclTrait;

	protected $Child;

	protected $name;

	/**
	 * @acl-action(action.foo)
	 */
	function foo() {
		return 'foo';
	}

	/**
	 * @acl-filter(filter.bar)
	 */
	function bar() {
		return 'bar';
	}
}
