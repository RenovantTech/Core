<?php
namespace test\db\orm;

/**
 * @orm(source="users")
 * @orm(insertFn="sp_people_insert, name, surname, age, score, @id")
 * @orm(updateFn="sp_people_update, id, name, surname, age, score")
 * @orm(deleteFn="sp_people_delete, id")
 * @orm-criteria(activeAge="active,EQ,1|age,GTE,?1", dateMonth="YEAR(lastTime) = ?1 AND MONTH(lastTime) = ?2")
 * @orm-order-by(nameASC="name ASC, surname ASC")
 * @orm-subset(mini="id, name, score", medium="id, active, name, score")
 * @orm-subset(large="id, active, name, age, score")
 */
class User2 {
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
	/** @orm(type="float") */
	protected $score;
	/** @orm(null)
	 * @validate(null, email) */
	protected $email;
	/** @orm(type="datetime", null) */
	protected $lastTime = null;
	/** @validate(regex="/^(OPEN|CLOSED)$/") */
	protected $notORM = 'OPEN';

	// ==================== EVENTS ================================================================

	protected function onSave() {
		$this->score++;
	}
}
