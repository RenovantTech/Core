<?php
namespace renovant\core\db\orm\util;
use renovant\core\sys,
	renovant\core\db\Procedure,
	renovant\core\db\Query,
	renovant\core\db\orm\Repository;
class QueryRunner {

	/**
	 * @param string $pdo
	 * @param string $class
	 * @param string|null $criteriaExp
	 * @return int
	 */
	static function count(string $pdo, string $class, ?string $criteriaExp=null) {
		$Query = (new Query(call_user_func($class.'::metadata', Repository::META_SQL, 'source'), null, $pdo))
			->setCriteriaDictionary(call_user_func($class.'::metadata', Repository::META_CRITERIA))
			->setOrderByDictionary(call_user_func($class.'::metadata', Repository::META_FETCH_ORDERBY))
			->criteriaExp($criteriaExp);
		return $Query->execCount();
	}

	/**
	 * @param string $pdo
	 * @param string $class
	 * @param object $Entity
	 * @param string|null $criteriaExp
	 * @return boolean
	 */
	static function deleteOne(string $pdo, string $class, object $Entity, ?string $criteriaExp=null) {
		if($deleteFn = call_user_func($class.'::metadata', Repository::META_SQL, 'deleteFn')) {
			self::execCall($pdo, $deleteFn, $Entity);
			return true;
		} else {
			$Query = (new Query(call_user_func($class.'::metadata', Repository::META_SQL, 'target'), null, $pdo))
				->criteriaExp(call_user_func($class.'::metadata', Repository::META_PKCRITERIA, $Entity))
				->criteriaExp($criteriaExp);
			return $Query->execDelete()==1 && $Query->errorCode()=='000000';
		}
	}

	/**
	 * @param string $pdo
	 * @param string $class
	 * @param integer|null $limit
	 * @param string|null $orderExp
	 * @param string|null $criteriaExp
	 * @return integer
	 */
	static function deleteAll(string $pdo, string $class, ?int $limit, ?string $orderExp, ?string $criteriaExp) {
		$Query = (new Query(call_user_func($class.'::metadata', Repository::META_SQL, 'target'), null, $pdo))
			->setCriteriaDictionary(call_user_func($class.'::metadata', Repository::META_CRITERIA))
			->setOrderByDictionary(call_user_func($class.'::metadata', Repository::META_FETCH_ORDERBY))
			->orderByExp($orderExp)
			->criteriaExp($criteriaExp)
			->limit($limit);
		return $Query->execDelete();
	}

	/**
	 * @param string $pdo
	 * @param string $class
	 * @param int|null $offset
	 * @param string|null $orderExp
	 * @param string|null $criteriaExp
	 * @param int $fetchMode fetch mode: FETCH_OBJ, FETCH_ARRAY, FETCH_RAW
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return object|array|false
	 * @throws \Exception
	 */
	static function fetchOne(string $pdo, string $class, ?int $offset, ?string $orderExp, ?string $criteriaExp, int $fetchMode=Repository::FETCH_OBJ, ?string $fetchSubset=null) {
		$subset = ($fetchSubset) ? call_user_func($class.'::metadata', Repository::META_FETCH_SUBSETS, $fetchSubset) : '*';
		$Query = (new Query(call_user_func($class.'::metadata', Repository::META_SQL, 'source'), $subset, $pdo))
			->setCriteriaDictionary(call_user_func($class.'::metadata', Repository::META_CRITERIA))
			->setOrderByDictionary(call_user_func($class.'::metadata', Repository::META_FETCH_ORDERBY))
			->orderByExp($orderExp)
			->criteriaExp($criteriaExp)
			->limit(1)
			->offset($offset);
		if($data = $Query->execSelect()->fetch(\PDO::FETCH_ASSOC)) {
			switch($fetchMode) {
				case Repository::FETCH_ARRAY:
					$Entity = DataMapper::sql2array($data, $class);
					break;
				case Repository::FETCH_JSON:
					$Entity = DataMapper::sql2json($data, $class);
					break;
				default: // Repository::FETCH_OBJ
					$Entity = new $class($data);
			}
			return $Entity;
		} else return false;
	}

	/**
	 * @param string $pdo
	 * @param string $class
	 * @param int|null $offset
	 * @param integer|null $limit
	 * @param string|null $orderExp
	 * @param string|null $criteriaExp
	 * @param int $fetchMode fetch mode: FETCH_OBJ, FETCH_ARRAY, FETCH_RAW
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return array
	 * @throws \Exception
	 */
	static function fetchAll(string $pdo, string $class, ?int $offset, ?int $limit, ?string $orderExp, ?string $criteriaExp, int $fetchMode=Repository::FETCH_OBJ, ?string $fetchSubset=null) {
		$subset = ($fetchSubset) ? call_user_func($class.'::metadata', Repository::META_FETCH_SUBSETS, $fetchSubset) : '*';
		$Query = (new Query(call_user_func($class.'::metadata', Repository::META_SQL, 'source'), $subset, $pdo))
			->setCriteriaDictionary(call_user_func($class.'::metadata', Repository::META_CRITERIA))
			->setOrderByDictionary(call_user_func($class.'::metadata', Repository::META_FETCH_ORDERBY))
			->orderByExp($orderExp)
			->criteriaExp($criteriaExp)
			->limit($limit)
			->offset($offset);
		$St = $Query->execSelect();
		$entities = [];
		while($data = $St->fetch(\PDO::FETCH_ASSOC)) {
			switch($fetchMode) {
				case Repository::FETCH_ARRAY:
					$entities[] = DataMapper::sql2array($data, $class);
					break;
				case Repository::FETCH_JSON:
					$entities[] = DataMapper::sql2json($data, $class);
					break;
				default: // Repository::FETCH_OBJ:
					$entities[] = new $class($data);
			}
		}
		return $entities;
	}

	/**
	 * @param string $pdo
	 * @param object $Entity
	 * @return boolean
	 */
	static function insert(string $pdo, object $Entity) {
		$data = DataMapper::object2sql($Entity);
		if($insertFn = call_user_func(get_class($Entity).'::metadata', Repository::META_SQL, 'insertFn')) {
			$pkeys = self::execCall($pdo, $insertFn, $Entity);
			foreach($pkeys as $k=>$v) $Entity->$k = $v;
			return true;
		} else {
			$fields = implode(',', array_keys(array_filter(call_user_func(get_class($Entity).'::metadata', Repository::META_PROPS), function($p) { return !$p['readonly']; })));
			$Query = (new Query(call_user_func(get_class($Entity).'::metadata', Repository::META_SQL, 'target'), $fields, $pdo));
			if($Query->execInsert($data)==1) {
				// fetch AUTO ID
				$pkeys = call_user_func(get_class($Entity).'::metadata', Repository::META_PKEYS);

				if(count($pkeys)==1 && isset(call_user_func(get_class($Entity).'::metadata', Repository::META_PROPS, $pkeys[0])['autoincrement'])) {
					$k = $pkeys[0];
					$v = (int)sys::pdo($pdo)->lastInsertId();
					$Entity->$k =$v;
				}
				return true;
			} else return false;
		}
	}

	/**
	 * @param string $pdo
	 * @param object $Entity
	 * @param array $changes
	 * @return boolean
	 */
	static function update(string $pdo, object $Entity, array $changes) {
		if($updateFn = call_user_func(get_class($Entity).'::metadata', Repository::META_SQL, 'updateFn')) {
			self::execCall($pdo, $updateFn, $Entity);
			return true;
		} else {
			$data = DataMapper::object2sql($Entity, $changes);
			$Query = (new Query(call_user_func(get_class($Entity).'::metadata', Repository::META_SQL, 'target'), null, $pdo))
				->criteriaExp(call_user_func(get_class($Entity).'::metadata', Repository::META_PKCRITERIA, $Entity));
			return in_array($Query->execUpdate($data), [0,1]) && $Query->errorCode()=='000000';
		}
	}

	protected static function execCall(string $pdo, string $storedFn, object $Entity) {
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
