<?php
namespace test\authz;
/**
 * @authz
 * @authz-role(admin)
 * @authz-permission(users:manage)
 * @authz-insert-permission(users:insert)
 * @authz-select-acl(schools="id")
 * @authz-update-roles-any(admin, manager)
 * @authz-update-permission(users:update)
 * @authz-delete-acl(schools="id")
 *
 * @orm(source="classes")
 */
class OrmAuthzMock {
	use \renovant\core\db\orm\EntityTrait;

	/** @orm(type="integer", primarykey, autoincrement) */
	protected $id;
	/** @orm(type="integer") */
	protected $school_id;
	/** @orm(type="integer", null) */
	protected $center_id;

	/** @orm
	 * @validate(enum="ACTIVE, NEW, OLD") */
	protected $status;
	/** @orm(null) */
	protected $code;
	/** @orm
	 * @validate(minLength=2) */
	protected $name;
	/** @orm(null) */
	protected $level;
	/** @orm(type="date")
	 * @validate(date) */
	protected $dateStart;
	/** @orm(type="date")
	 * @validate(date) */
	protected $dateEnd;
}
