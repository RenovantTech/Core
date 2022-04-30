<?php
namespace renovant\core\db\orm\util;
use renovant\core\sys,
	renovant\core\db\Procedure,
	renovant\core\db\Query,
	renovant\core\db\orm\EntityTrait,
	renovant\core\db\orm\Repository;
class QueryRunner {

	protected Metadata $Metadata;
	/** PDO instance ID */
	protected ?string $pdo;

	function __construct(?string $pdo, Metadata $Metadata) {
		$this->Metadata = $Metadata;
		$this->pdo = $pdo;
	}

	function count(?string $criteriaExp=null): int {
		$Query = (new Query($this->Metadata->sql('source'), null, $this->pdo))
			->setCriteriaDictionary($this->Metadata->criteria())
			->setOrderByDictionary($this->Metadata->fetchOrderBy())
			->criteriaExp($criteriaExp);
		return $Query->execCount();
	}

	function deleteOne(object $Entity, ?string $criteriaExp=null): bool {
		if($deleteFn = $this->Metadata->sql('deleteFn')) {
			$this->execCall($deleteFn, $Entity);
			return true;
		} else {
			$Query = (new Query($this->Metadata->sql('target'), null, $this->pdo))
				->criteriaExp($this->Metadata->pkCriteria($Entity))
				->criteriaExp($criteriaExp);
			return $Query->execDelete()==1 && $Query->errorCode()=='000000';
		}
	}

	function deleteAll(?int $limit, ?string $orderExp, ?string $criteriaExp): int {
		$Query = (new Query($this->Metadata->sql('target'), null, $this->pdo))
			->setCriteriaDictionary($this->Metadata->criteria())
			->setOrderByDictionary($this->Metadata->fetchOrderBy())
			->orderByExp($orderExp)
			->criteriaExp($criteriaExp)
			->limit($limit);
		return $Query->execDelete();
	}

	function fetchOne(string $class, ?int $offset, ?string $orderExp, ?string $criteriaExp, int $fetchMode=Repository::FETCH_OBJ, ?string $fetchSubset=null): object|array|false {
		$subset = ($fetchSubset) ? $this->Metadata->fetchSubset($fetchSubset) : '*';
		$Query = (new Query($this->Metadata->sql('source'), $subset, $this->pdo))
			->setCriteriaDictionary($this->Metadata->criteria())
			->setOrderByDictionary($this->Metadata->fetchOrderBy())
			->orderByExp($orderExp)
			->criteriaExp($criteriaExp)
			->limit(1)
			->offset($offset);
		if($data = $Query->execSelect()->fetch(\PDO::FETCH_ASSOC)) {
			return match ($fetchMode) {
				Repository::FETCH_ARRAY => DataMapper::sql2array($data, $class),
				Repository::FETCH_JSON => DataMapper::sql2json($data, $class),
				Repository::FETCH_OBJ => new $class($data)
			};
		} else return false;
	}

	function fetchAll(string $class, ?int $offset, ?int $limit, ?string $orderExp, ?string $criteriaExp, int $fetchMode=Repository::FETCH_OBJ, ?string $fetchSubset=null): array {
		$subset = ($fetchSubset) ? $this->Metadata->fetchSubset($fetchSubset) : '*';
		$Query = (new Query($this->Metadata->sql('source'), $subset, $this->pdo))
			->setCriteriaDictionary($this->Metadata->criteria())
			->setOrderByDictionary($this->Metadata->fetchOrderBy())
			->orderByExp($orderExp)
			->criteriaExp($criteriaExp)
			->limit($limit)
			->offset($offset);
		$St = $Query->execSelect();
		$entities = [];
		while($data = $St->fetch(\PDO::FETCH_ASSOC)) {
			$entities[] = match ($fetchMode) {
				Repository::FETCH_ARRAY => DataMapper::sql2array($data, $class),
				Repository::FETCH_JSON => DataMapper::sql2json($data, $class),
				Repository::FETCH_OBJ => new $class($data)
			};
		}
		return $entities;
	}

	function insert(object $Entity): bool {
		/** @var object|EntityTrait $Entity */
		$data = DataMapper::object2sql($Entity);
		if($insertFn = $this->Metadata->sql('insertFn')) {
			$pKeys = $this->execCall($insertFn, $Entity);
			foreach($pKeys as $k=>$v) $Entity->$k = $v;
			return true;
		} else {
			$fields = implode(',', array_keys(array_filter($this->Metadata->properties(), function($p) { return !$p['readonly']; })));
			$Query = (new Query($this->Metadata->sql('target'), $fields, $this->pdo));
			if($Query->execInsert($data)==1) {
				// fetch AUTO ID
				$pKeys = $this->Metadata->pKeys();
				if(count($pKeys)==1 && isset($this->Metadata->property($pKeys[0])['autoincrement'])) {
					$k = $pKeys[0];
					$Entity->$k = (int)sys::pdo($this->pdo)->lastInsertId();
				}
				return true;
			} else return false;
		}
	}

	function update(object $Entity, array $changes, ?string $criteriaExp): bool {
		/** @var object|EntityTrait $Entity */
		if($updateFn = $this->Metadata->sql('updateFn')) {
			$this->execCall($updateFn, $Entity);
			return true;
		} else {
			$data = DataMapper::object2sql($Entity, $changes);
			$Query = (new Query($this->Metadata->sql('target'), null, $this->pdo))
				->criteriaExp($criteriaExp);
			return in_array($Query->execUpdate($data), [0,1]) && $Query->errorCode()=='000000';
		}
	}

	protected function execCall(string $storedFn, object $Entity) {
		$data = DataMapper::object2sql($Entity);
		$params = explode(',',str_replace(' ','',$storedFn));
		$procedure = array_shift($params);
		$Procedure = (new Procedure($procedure, $this->pdo));
		$execParams = [];
		foreach($params as $k){
			if($k[0]!='@') $execParams[$k] = $data[$k];
		}
		return $Procedure->exec($execParams);
	}
}
