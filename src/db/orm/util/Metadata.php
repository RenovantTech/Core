<?php
namespace renovant\core\db\orm\util;

class Metadata {
	public const CACHE_TAG = 'orm:metadata';

	/** @@orm-events */
	protected array $events = [];
	/** @orm-criteria */
	protected array $criteria = [];
	/** @orm-fetch-subset */
	protected array $fetchSubsets = [];
	/** @orm-order-by */
	protected array $fetchOrderBy = [];
	/** @orm-validate-subset */
	protected array $validateSubsets = [];

	/** @orm(..., primarykey) */
	protected array $pKeys = [];
	/** @orm(..., primarykey) */
	protected string $pkCriteria = '';
	/** @orm(....) */
	protected array $properties = [];

	protected array $sql = [
		'source'   => '',
		'target'   => '',
		'insertFn' => '',
		'updateFn' => '',
		'deleteFn' => ''
	];

	/**
	 * @throws \ReflectionException
	 * @throws \renovant\core\db\orm\Exception
	 */
	public function __construct(string $entityClass) {
		list($this->events, $this->criteria, $this->fetchSubsets, $this->fetchOrderBy, $this->validateSubsets, $this->pKeys, $this->pkCriteria, $this->properties, $this->sql) = MetadataParser::parse($entityClass);
	}

	public function event(string $event): string|bool {
		return $this->events[$event] ?? false;
	}

	public function criteria() {
		return $this->criteria;
	}

	public function fetchOrderBy(): array {
		return $this->fetchOrderBy;
	}

	public function fetchSubset(string $subset): string {
		if (!isset($this->fetchSubsets[$subset])) {
			trigger_error('Invalid FETCH SUBSET requested: ' . $subset, E_USER_WARNING);
			return '*';
		}
		return $this->fetchSubsets[$subset];
	}

	public function pkCriteria($param): string {
		if (is_object($param)) {
			$keys = [];
			foreach ($this->pKeys as $k) {
				$keys[] = $param->$k;
			}
		} else {
			$keys = $param;
		}
		return preg_replace(array_fill(0, count($this->pKeys), '/\?/'), $keys, $this->pkCriteria, 1);
	}

	public function pKeys(): array {
		return $this->pKeys;
	}

	public function property(string $prop): ?array {
		if (!isset($this->properties[$prop])) {
			trigger_error('Undefined ORM metadata for property "' . $prop . '", must have tag @orm', E_USER_WARNING);
			return null;
		} else {
			return $this->properties[$prop];
		}
	}

	public function properties(): array {
		return $this->properties;
	}

	public function sql(string $conf): ?string {
		return $this->sql[$conf] ?? null;
	}

	public function validateSubset($subset): array {
		if (isset($this->validateSubsets[$subset])) {
			return explode(',', $this->validateSubsets[$subset]);
		}
		trigger_error('Invalid VALIDATE SUBSET requested: ' . $subset, E_USER_WARNING);
		return array_keys($this->properties);
	}
}
