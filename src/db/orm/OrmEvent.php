<?php
namespace renovant\core\db\orm;

use renovant\core\db\Query;

class OrmEvent extends \renovant\core\event\Event {
	public const EVENT_PRE_COUNT      = 'orm:pre-count';
	public const EVENT_PRE_DELETE     = 'orm:pre-delete';
	public const EVENT_PRE_DELETE_ALL = 'orm:pre-delete-all';
	public const EVENT_PRE_FETCH      = 'orm:pre-fetch';
	public const EVENT_PRE_FETCH_ALL  = 'orm:pre-fetch-all';
	public const EVENT_PRE_INSERT     = 'orm:pre-insert';
	public const EVENT_PRE_UPDATE     = 'orm:pre-update';

	public const EVENT_POST_DELETE     = 'orm:post-delete';
	public const EVENT_POST_DELETE_ALL = 'orm:post-delete-all';
	public const EVENT_POST_FETCH      = 'orm:post-fetch';
	public const EVENT_POST_FETCH_ALL  = 'orm:post-fetch-all';
	public const EVENT_POST_INSERT     = 'orm:post-insert';
	public const EVENT_POST_UPDATE     = 'orm:post-update';

	/** SQL criteria exp */
	protected array $criteriaExp = [];
	/** Entity */
	protected object $Entity;
	/** Entity array*/
	protected array $entities;
	/** ORM Repository */
	protected Repository $Repository;
	/** Exception, if any
	 * @var \Exception */
	protected $Exception;

	public function __construct(Repository $Repository) {
		$this->Repository = $Repository;
	}

	/**
	 * Add Criteria Expression */
	public function criteriaExp(?string $criteriaExp): self {
		if (!empty($criteriaExp)) {
			$this->criteriaExp = array_merge($this->criteriaExp, explode(Query::EXP_DELIMITER, $criteriaExp));
		}
		return $this;
	}

	/**
	 * Get current Repository */
	public function getRepository(): Repository {
		return $this->Repository;
	}

	/**
	 * Get current Entity */
	public function getEntity(): object {
		return $this->Entity;
	}

	/**
	 * Get current Entity */
	public function getEntities(): array {
		return $this->entities;
	}

	/**
	 * Get criteria expression */
	public function getCriteriaExp(): string {
		return implode(Query::EXP_DELIMITER, $this->criteriaExp);
	}

	/**
	 * Get current Exception, if any
	 * @return \Exception|null
	 */
	public function getException() {
		return $this->Exception;
	}

	public function setEntity(object $Entity): self {
		$this->Entity = $Entity;
		return $this;
	}

	public function setEntities(array $entities): self {
		$this->entities = $entities;
		return $this;
	}

	public function setException(\Exception $Exception): self {
		$this->Exception = $Exception;
		return $this;
	}
}
