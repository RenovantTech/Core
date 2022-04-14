<?php
namespace renovant\core\db\orm;
use renovant\core\db\Query;
class OrmEvent extends \renovant\core\event\Event {

	const EVENT_PRE_COUNT		= 'orm:pre-count';
	const EVENT_PRE_DELETE		= 'orm:pre-delete';
	const EVENT_PRE_DELETE_ALL	= 'orm:pre-delete-all';
	const EVENT_PRE_FETCH		= 'orm:pre-fetch';
	const EVENT_PRE_FETCH_ALL	= 'orm:pre-fetch-all';
	const EVENT_PRE_INSERT		= 'orm:pre-insert';
	const EVENT_PRE_UPDATE		= 'orm:pre-update';

	const EVENT_POST_DELETE		= 'orm:post-delete';
	const EVENT_POST_DELETE_ALL	= 'orm:post-delete-all';
	const EVENT_POST_FETCH		= 'orm:post-fetch';
	const EVENT_POST_FETCH_ALL	= 'orm:post-fetch-all';
	const EVENT_POST_INSERT		= 'orm:post-insert';
	const EVENT_POST_UPDATE		= 'orm:post-update';

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

	function __construct(Repository $Repository) {
		$this->Repository = $Repository;
	}

	/**
	 * Add Criteria Expression */
	function criteriaExp(?string $criteriaExp): self {
		if(!empty($criteriaExp)) $this->criteriaExp = array_merge($this->criteriaExp, explode(Query::EXP_DELIMITER, $criteriaExp));
		return $this;
	}

	/**
	 * Get current Repository */
	function getRepository(): Repository {
		return $this->Repository;
	}

	/**
	 * Get current Entity */
	function getEntity(): object {
		return $this->Entity;
	}

	/**
	 * Get current Entity */
	function getEntities(): array {
		return $this->entities;
	}

	/**
	 * Get criteria expression */
	function getCriteriaExp(): string {
		return implode(Query::EXP_DELIMITER, $this->criteriaExp);
	}

	/**
	 * Get current Exception, if any
	 * @return \Exception|null
	 */
	function getException() {
		return $this->Exception;
	}

	function setEntity(object $Entity): self {
		$this->Entity = $Entity;
		return $this;
	}

	function setEntities(array $entities): self {
		$this->entities = $entities;
		return $this;
	}

	function setException(\Exception $Exception): self {
		$this->Exception = $Exception;
		return $this;
	}
}
