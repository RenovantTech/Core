<?php
namespace test\authz;
use renovant\core\authz\OrmAuthz,
	renovant\core\authz\OrmTagsParser,
	renovant\core\util\reflection\ReflectionObject;

class OrmTagParserTest extends \PHPUnit\Framework\TestCase {

	function testParse() {
		$ObjAuthz = OrmTagsParser::parse(OrmTagParserMock::class);
		$this->assertInstanceOf(OrmAuthz::class, $ObjAuthz);
		$RefObj = new ReflectionObject($ObjAuthz);

		// allows
		$RefProp = $RefObj->getProperty('allows');
		$RefProp->setAccessible(true);
		$this->assertEquals([
			'_' => [
				'roles' => [ 'super-admin', 'sys-admin' ],
				'permissions' => [ 'super-perm', 'sys-perm' ]
			]
		], $RefProp->getValue($ObjAuthz));

		// roles
		$RefProp = $RefObj->getProperty('roles');
		$RefProp->setAccessible(true);
		$this->assertEquals([
			'_' => [ 'admin' ],
			'UPDATE' => [ 'admin', 'manager' ]
		], $RefProp->getValue($ObjAuthz));

		// op_roles
		$RefProp = $RefObj->getProperty('op_roles');
		$RefProp->setAccessible(true);
		$this->assertEquals([
			'_' => OrmAuthz::OP_ONE,
			'UPDATE' => OrmAuthz::OP_ANY
		], $RefProp->getValue($ObjAuthz));

		// perms
		$RefProp = $RefObj->getProperty('perms');
		$RefProp->setAccessible(true);
		$this->assertEquals([
			'_' => [ 'users:manage' ],
			'INSERT' => [ 'users:insert' ],
			'SELECT' => [ 'users:manage' ],
			'UPDATE' => [ 'users:manage', 'users:update' ]
		], $RefProp->getValue($ObjAuthz));

		// op_perms
		$RefProp = $RefObj->getProperty('op_perms');
		$RefProp->setAccessible(true);
		$this->assertEquals([
			'_' => OrmAuthz::OP_ONE,
			'INSERT' => OrmAuthz::OP_ONE,
			'SELECT' => OrmAuthz::OP_ONE,
			'UPDATE' => OrmAuthz::OP_ANY
		], $RefProp->getValue($ObjAuthz));

		// acls
		$RefProp = $RefObj->getProperty('acls');
		$RefProp->setAccessible(true);
		$this->assertEquals([
			'SELECT' => [ 'users'=>'id' ],
			'DELETE' => [ 'users'=>'id' ]
		], $RefProp->getValue($ObjAuthz));

		// op_acls
		$RefProp = $RefObj->getProperty('op_acls');
		$RefProp->setAccessible(true);
		$this->assertEquals([
			'SELECT' => OrmAuthz::OP_ONE,
			'DELETE' => OrmAuthz::OP_ONE
		], $RefProp->getValue($ObjAuthz));
	}
}
