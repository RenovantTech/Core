<?php
namespace test\authz\orm;
/**
 * @authz-allow-roles(sys-admin)
 * @authz-insert-role(admin:insert)
 * @authz-select-roles-any(admin:select1, admin:select2)
 * @authz-update-roles-all(admin:update1, admin:update2)
 *
 * @orm(source="classes")
 *
 * @property $id
 */
class Entity1 {
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
}
