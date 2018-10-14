<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace metadigit\core\db\orm;
use metadigit\core\db\Query;
/**
 * ORM Event
 * Main event passed throughout ORM flow.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class OrmEvent extends \metadigit\core\event\Event {

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

	/** SQL criteria exp
	 * @var array */
	protected $criteriaExp = [];
	/** Entity
	 * @var object */
	protected $Entity;
	/** Entity array
	 * @var array */
	protected $entities;
	/** ORM Repository
	 * @var Repository */
	protected $Repository;
	/** Exception, if any
	 * @var \Exception */
	protected $Exception;

	function __construct(Repository $Repository) {
		$this->Repository = $Repository;
	}

	/**
	 * Add Criteria Expression
	 * @param string $criteriaExp
	 * @return $this
	 */
	function criteriaExp($criteriaExp) {
		if(!empty($criteriaExp)) $this->criteriaExp = array_merge($this->criteriaExp, explode(Query::EXP_DELIMITER, $criteriaExp));
		return $this;
	}

	/**
	 * Get current Repository
	 * @return Repository
	 */
	function getRepository() {
		return $this->Repository;
	}

	/**
	 * Get current Entity
	 * @return object
	 */
	function getEntity() {
		return $this->Entity;
	}

	/**
	 * Get current Entity
	 * @return object
	 */
	function getEntities() {
		return $this->entities;
	}

	/**
	 * Get criteria expression
	 * @return string
	 */
	function getCriteriaExp() {
		return implode(Query::EXP_DELIMITER, $this->criteriaExp);
	}

	/**
	 * Get current Exception, if any
	 * @return \Exception|null
	 */
	function getException() {
		return $this->Exception;
	}

	/**
	 * @param object $Entity
	 * @return $this
	 */
	function setEntity($Entity) {
		$this->Entity = $Entity;
		return $this;
	}

	/**
	 * @param array $entities
	 * @return $this
	 */
	function setEntities(array $entities) {
		$this->entities = $entities;
		return $this;
	}

	/**
	 * @param \Exception $Exception
	 * @return $this
	 */
	function setException(\Exception $Exception) {
		$this->Exception = $Exception;
		return $this;
	}
}
