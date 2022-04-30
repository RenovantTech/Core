<?php
namespace test\authz\orm;
/**
 * @authz-allow-permissions(perm:all)
 * @authz-insert-permission(perm:insert)
 * @authz-select-permissions-any(perm:select1, perm:select2)
 * @authz-update-permissions-all(perm:update1, perm:update2)
 *
 * @orm(source="classes")
 *
 * @property $id
 */
class Entity2 {
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
