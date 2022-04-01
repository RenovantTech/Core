<?php
namespace test\authz;
/**
 * @authz-role(role.service2)
 */
class AuthzTraitMockService {
	use \renovant\core\authz\AuthzTrait;

	/**
	 * @authz-action(action.foo)
	 */
	function foo() {
		return 'foo';
	}

	/**
	 * @authz-filter(filter.bar)
	 */
	function bar() {
		return 'bar';
	}

	/**
	 * @authz-role(role.zoo)
	 */
	function zoo() {
		return 'zoo';
	}
}
