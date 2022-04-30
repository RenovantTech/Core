<?php
namespace test\authz;
/**
 * @authz-allow-roles(super-admin, sys-admin)
 * @authz-allow-permissions(super-perm, sys-perm)
 * @authz-role(admin)
 * @authz-permission(users:manage)
 * @authz-insert-permission(users:insert)
 * @authz-select-permission(users:manage)
 * @authz-select-acl(users="id")
 * @authz-update-roles-any(admin, manager)
 * @authz-update-permissions-any(users:manage, users:update)
 * @authz-delete-acl(users="id")
 *
 * @orm(source="users")
 */
class OrmTagParserMock {
	use \renovant\core\db\orm\EntityTrait;

	/** @orm(type="integer", primarykey, autoincrement) */
	protected $id;
	/** @orm(type="boolean") */
	protected $active = true;
	/** @orm
	 * @validate(minLength=4) */
	protected $name;
	/** @orm
	 * @validate(maxLength=45) */
	protected $surname;
	/** @orm(type="integer", null)
	 * @validate(min=15) */
	protected $age = 20;
	/** @orm(type="date", null)
	 * @validate(null, date) */
	protected $birthday;
	/** @orm(type="float") */
	protected $score;
	/** @orm(null)
	 * @validate(null, email) */
	protected $email;
	/** @orm(type="datetime", null) */
	protected $lastTime = null;
	/** @orm(type="datetime", readonly) */
	protected $updatedAt;

	/** @validate(regex="/^(OPEN|CLOSED)$/") */
	protected $notORM = 'OPEN';
}
