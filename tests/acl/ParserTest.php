<?php
namespace test\acl;
use renovant\core\sys,
	renovant\core\util\reflection\ReflectionObject;

class ParserTest extends \PHPUnit\Framework\TestCase {


	/**
	 * @throws \ReflectionException
	 * @throws \renovant\core\container\ContainerException
	 */
	function testParse() {
		/** @var MockService $MockService */
		$MockService = sys::context()->container()->get('test.acl.MockService');
		$this->assertInstanceOf(MockService::class, $MockService);

		$RefObj = new ReflectionObject($MockService);

		$this->assertTrue($RefObj->hasProperty('_acl_actions'));
		$this->assertTrue($RefObj->hasProperty('_acl_filters'));
		$this->assertTrue($RefObj->hasProperty('_acl_roles'));

		$RefProp = $RefObj->getProperty('_acl_actions');
		$RefProp->setAccessible(true);
		$_acl_actions = $RefProp->getValue($MockService);
		$this->assertEquals([
			'_' => [ 'mock.action1', 'mock.action2', 'mock.action3'],
			'foo' => [ 'action.foo1', 'foo.action2', 'foo.action3'],
			'bar' => [ 'action.bar1', 'bar.action2', 'bar.action3'],
		], $_acl_actions);



		$RefProp = $RefObj->getProperty('_acl_filters');
		$RefProp->setAccessible(true);
		$_acl_filters = $RefProp->getValue($MockService);
		$this->assertEquals([
			'_' => [ 'mock.filter1', 'mock.filter2', 'mock.filter3'],
			'foo' => [ 'filter.foo1', 'foo.filter2', 'foo.filter3'],
			'bar' => [ 'filter.bar1', 'bar.filter2', 'bar.filter3'],
		], $_acl_filters);


		$RefProp = $RefObj->getProperty('_acl_roles');
		$RefProp->setAccessible(true);
		$_acl_roles = $RefProp->getValue($MockService);
		$this->assertEquals([
			'_' => [ 'mock.role1', 'mock.role2', 'mock.role3'],
			'foo' => [ 'role.foo1', 'foo.role2', 'foo.role3'],
			'bar' => [ 'role.bar1', 'bar.role2', 'bar.role3'],
		], $_acl_roles);
	}
}
