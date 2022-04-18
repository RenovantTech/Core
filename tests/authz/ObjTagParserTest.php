<?php
namespace test\authz;
use renovant\core\sys,
	renovant\core\authz\ObjAuthz,
	renovant\core\authz\ObjTagsParser,
	renovant\core\util\reflection\ReflectionObject;

class ObjTagParserTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @throws \ReflectionException
	 * @throws \renovant\core\container\ContainerException
	 */
	function testParse() {
		/** @var ObjTagParserMock $MockService */
		$MockService = sys::context()->container()->get('test.authz.ObjTagParserMock');
		$this->assertInstanceOf(ObjTagParserMock::class, $MockService);

		$ObjAuthz = ObjTagsParser::parse($MockService);
		$this->assertInstanceOf(ObjAuthz::class, $ObjAuthz);

		$RefObj = new ReflectionObject($ObjAuthz);

		// roles
		$RefProp = $RefObj->getProperty('roles');
		$RefProp->setAccessible(true);
		$this->assertEquals([
			'_' => [ 'mock.role1' ],
			'foo' => [ 'foo.role2', 'foo.role3' ],
			'bar' => [ 'bar.role2', 'bar.role3' ]
		], $RefProp->getValue($ObjAuthz));

		// op_roles
		$RefProp = $RefObj->getProperty('op_roles');
		$RefProp->setAccessible(true);
		$this->assertEquals([
			'_' => ObjAuthz::OP_ONE,
			'foo' => ObjAuthz::OP_ANY,
			'bar' => ObjAuthz::OP_ALL
		], $RefProp->getValue($ObjAuthz));

		// perms
		$RefProp = $RefObj->getProperty('perms');
		$RefProp->setAccessible(true);
		$this->assertEquals([
			'_' => [ 'mock.perm1' ],
			'foo' => [ 'foo.perm1', 'foo.perm2' ]
		], $RefProp->getValue($ObjAuthz));

		// op_perms
		$RefProp = $RefObj->getProperty('op_perms');
		$RefProp->setAccessible(true);
		$this->assertEquals([
			'_' => ObjAuthz::OP_ONE,
			'foo' => ObjAuthz::OP_ANY
		], $RefProp->getValue($ObjAuthz));

		// acls
		$RefProp = $RefObj->getProperty('acls');
		$RefProp->setAccessible(true);
		$this->assertEquals([
			'reboot' => [ 'reboot'=>'$id' ],
			'query' => [ 'area'=>'$area', 'district'=>'$district' ]
		], $RefProp->getValue($ObjAuthz));

		// op_acls
		$RefProp = $RefObj->getProperty('op_acls');
		$RefProp->setAccessible(true);
		$this->assertEquals([
			'reboot' => ObjAuthz::OP_ONE,
			'query' => ObjAuthz::OP_ALL
		], $RefProp->getValue($ObjAuthz));

		// methodsParams
		$RefProp = $RefObj->getProperty('methodsParams');
		$RefProp->setAccessible(true);
		$this->assertEquals([
			'reboot' => [
				'region' => [
					'index' => 0,
					'class' => null,
					'type' => 'string',
					'default' => null
				],
				'id' => [
					'index' => 1,
					'class' => null,
					'type' => 'int',
					'default' => null
				]
			],
			'query' => [
				'area' => [
					'index' => 0,
					'class' => null,
					'type' => 'string',
					'default' => null
				],
				'district' => [
					'index' => 1,
					'class' => null,
					'type' => 'string',
					'default' => null
				]
			]
		], $RefProp->getValue($ObjAuthz));
	}
}
