<?php
namespace test\authz;
use renovant\core\sys,
	renovant\core\util\reflection\ReflectionObject;

class ParserTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @throws \ReflectionException
	 * @throws \renovant\core\container\ContainerException
	 */
	function testParse() {
		/** @var ParserMockService $MockService */
		$MockService = sys::context()->container()->get('test.authz.ParserMockService');
		$this->assertInstanceOf(ParserMockService::class, $MockService);

		$RefObj = new ReflectionObject($MockService);
		$this->assertTrue($RefObj->hasProperty('_authz'));

		$RefProp = $RefObj->getProperty('_authz');
		$RefProp->setAccessible(true);
		$_authz = $RefProp->getValue($MockService);

		$this->assertEquals([
			'roles' => [ 'mock.role1' ],
			'permissions' => [ 'mock.perm1' ]
		], $_authz['_']);


		$this->assertEquals([
			'roles' => [ 'foo.role2', 'foo.role3' ],
			'roles_op' => 'ANY',
			'permissions' => [ 'foo.perm1', 'foo.perm2' ],
			'permissions_op' => 'ANY'
		], $_authz['foo']);

		$this->assertEquals([
			'roles' => [ 'bar.role2', 'bar.role3' ],
			'roles_op' => 'ALL'
		], $_authz['bar']);

		$this->assertEquals([
			'acl' => [
				[ 'reboot' => '$id' ]
			],
		], $_authz['reboot']);

		$this->assertEquals([
			'acl' => [
				[ 'area' => '$area' ],
				[ 'district' => '$district' ]
			],
			'acl_op' => 'ALL'
		], $_authz['query']);
	}
}
