<?php
namespace renovant\core\db\orm\util;
use renovant\core\sys,
	renovant\core\db\Procedure,
	renovant\core\db\Query,
	renovant\core\db\orm\EntityTrait,
	renovant\core\db\orm\Repository;
class QueryRunner {

	static function count(?string $pdo, string $class, ?string $criteriaExp=null): int {
		$Query = (new Query(call_user_func($class.'::metadata')->sql('source'), null, $pdo))
			->setCriteriaDictionary(call_user_func($class.'::metadata')->criteria())
			->setOrderByDictionary(call_user_func($class.'::metadata')->fetchOrderBy())
			->criteriaExp($criteriaExp);
		return $Query->execCount();
	}

	static function deleteOne(?string $pdo, string $class, object $Entity, ?string $criteriaExp=null): bool {
		if($deleteFn = call_user_func($class.'::metadata')->sql('deleteFn')) {
			self::execCall($pdo, $deleteFn, $Entity);
			return true;
		} else {
			$Query = (new Query(call_user_func($class.'::metadata')->sql('target'), null, $pdo))
				->criteriaExp(call_user_func($class.'::metadata')->pkCriteria($Entity))
				->criteriaExp($criteriaExp);
			return $Query->execDelete()==1 && $Query->errorCode()=='000000';
		}
	}

	static function deleteAll(?string $pdo, string $class, ?int $limit, ?string $orderExp, ?string $criteriaExp): int {
		$Query = (new Query(call_user_func($class.'::metadata')->sql('target'), null, $pdo))
			->setCriteriaDictionary(call_user_func($class.'::metadata')->criteria())
			->setOrderByDictionary(call_user_func($class.'::metadata')->fetchOrderBy())
			->orderByExp($orderExp)
			->criteriaExp($criteriaExp)
			->limit($limit);
		return $Query->execDelete();
	}

	/**
	 * @throws \Exception
	 */
	static function fetchOne(?string $pdo, string $class, ?int $offset, ?string $orderExp, ?string $criteriaExp, int $fetchMode=Repository::FETCH_OBJ, ?string $fetchSubset=null): object|array|false {
		$subset = ($fetchSubset) ? call_user_func($class.'::metadata')->fetchSubset($fetchSubset) : '*';
		$Query = (new Query(call_user_func($class.'::metadata')->sql('source'), $subset, $pdo))
			->setCriteriaDictionary(call_user_func($class.'::metadata')->criteria())
			->setOrderByDictionary(call_user_func($class.'::metadata')->fetchOrderBy())
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

	/**
	 * @throws \Exception
	 */
	static function fetchAll(?string $pdo, string $class, ?int $offset, ?int $limit, ?string $orderExp, ?string $criteriaExp, int $fetchMode=Repository::FETCH_OBJ, ?string $fetchSubset=null): array {
		$subset = ($fetchSubset) ? call_user_func($class.'::metadata')->fetchSubset($fetchSubset) : '*';
		$Query = (new Query(call_user_func($class.'::metadata')->sql('source'), $subset, $pdo))
			->setCriteriaDictionary(call_user_func($class.'::metadata')->criteria())
			->setOrderByDictionary(call_user_func($class.'::metadata')->fetchOrderBy())
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

	static function insert(?string $pdo, object $Entity): bool {
		/** @var object|EntityTrait $Entity */
		$data = DataMapper::object2sql($Entity);
		if($insertFn = $Entity::metadata()->sql('insertFn')) {
			$pKeys = self::execCall($pdo, $insertFn, $Entity);
			foreach($pKeys as $k=>$v) $Entity->$k = $v;
			return true;
		} else {
			$fields = implode(',', array_keys(array_filter($Entity::metadata()->properties(), function($p) { return !$p['readonly']; })));
			$Query = (new Query($Entity::metadata()->sql('target'), $fields, $pdo));
			if($Query->execInsert($data)==1) {
				// fetch AUTO ID
				$pKeys = $Entity::metadata()->pKeys();

				if(count($pKeys)==1 && isset($Entity::metadata()->property($pKeys[0])['autoincrement'])) {
					$k = $pKeys[0];
					$v = (int)sys::pdo($pdo)->lastInsertId();
					$Entity->$k =$v;
				}
				return true;
			} else return false;
		}
	}

	static function update(?string $pdo, object $Entity, array $changes): bool {
		/** @var object|EntityTrait $Entity */
		if($updateFn = $Entity::metadata()->sql('updateFn')) {
			self::execCall($pdo, $updateFn, $Entity);
			return true;
		} else {
			$data = DataMapper::object2sql($Entity, $changes);
			$Query = (new Query($Entity::metadata()->sql('target'), null, $pdo))
				->criteriaExp($Entity::metadata()->pkCriteria($Entity));
			return in_array($Query->execUpdate($data), [0,1]) && $Query->errorCode()=='000000';
		}
	}

	protected static function execCall(?string $pdo, string $storedFn, object $Entity) {
		$data = DataMapper::object2sql($Entity);
		$params = explode(',',str_replace(' ','',$storedFn));
		$procedure = array_shift($params);
		$Procedure = (new Procedure($procedure, $pdo));
		$execParams = [];
		foreach($params as $k){
			if($k[0]!='@') $execParams[$k] = $data[$k];
		}
		return $Procedure->exec($execParams);
	}
}
