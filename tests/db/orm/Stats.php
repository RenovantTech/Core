<?php
namespace test\db\orm;

/**
 * @orm(source="stats")
 */
class Stats {
	use \renovant\core\db\orm\EntityTrait;

	/** @orm(type="string", primarykey) */
	protected $code;
	/** @orm(type="integer", primarykey) */
	protected $year;
	/** @orm(type="float") */
	protected $score;
}
