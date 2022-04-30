<?php
namespace test\authz\orm;
/**
 * @authz-acl(acl:id="id")
 * @authz-insert-acl-any(acl:school="school_id", acl:type="type_id")
 * @authz-update-acl-all(acl:school="school_id", acl:type="type_id")
 *
 * @orm(source="classes")
 *
 * @property $id
 */
class Entity3 {
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
