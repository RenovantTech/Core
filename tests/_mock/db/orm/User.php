<?php
namespace mock\db\orm;

/**
 * @orm(source="users")
 * @orm-criteria(activeAge="active,EQ,1|age,GTE,?1", dateMonth="YEAR(lastTime) = ?1 AND MONTH(lastTime) = ?2")
 * @orm-order-by(nameASC="name ASC, surname ASC")
 * @orm-fetch-subset(mini="id, name, score", medium="id, active, name, score")
 * @orm-fetch-subset(large="id, active, name, age, score")
 */
class User {
	use \metadigit\core\db\orm\EntityTrait;

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

	// ==================== EVENTS ================================================================

	protected function onSave() {
		$this->score++;
	}
}
