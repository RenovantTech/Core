<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\db\orm\util;
use renovant\core\sys,
	renovant\core\db\Query,
	renovant\core\db\orm\Metadata,
	renovant\core\db\orm\Repository;
/**
 * ORM QueryRunner
 * Helper class that build & execute queries
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
class QueryRunner {

	/**
	 * @param string $pdo
	 * @param string $class
	 * @param string|null $criteriaExp
	 * @return int
	 */
	static function count(string $pdo, string $class, ?string $criteriaExp=null) {
		$Metadata = Metadata::get($class);
		$Query = (new Query($pdo))
			->on($Metadata->sql('source'))
			->setCriteriaDictionary($Metadata->criteria())
			->setOrderByDictionary($Metadata->order())
			->criteriaExp($criteriaExp);
		return (int) $Query->execCount();
	}

	/**
	 * @param string $pdo
	 * @param string $class
	 * @param object $Entity
	 * @param string|null $criteriaExp
	 * @return boolean
	 */
	static function deleteOne(string $pdo, string $class, object $Entity, ?string $criteriaExp=null) {
		$Metadata = Metadata::get($class);
		if($deleteFn = $Metadata->sql('deleteFn')) {
			$data = DataMapper::object2sql($Entity);
			$params = explode(',',str_replace(' ','',$deleteFn));
			$procedure = array_shift($params);
			$Query = (new Query($pdo))->on($procedure);
			$execParams = [];
			foreach($params as $k){
				if($k[0]!='@') $execParams[$k] = $data[$k];
			}
			$Query->execCall($execParams);
			return true;
		} else {
			$Query = (new Query($pdo))
				->on($Metadata->sql('target'))
				->criteriaExp($Metadata->pkCriteria($Entity))
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
		$Metadata = Metadata::get($class);
		$Query = (new Query($pdo))
			->on($Metadata->sql('target'))
			->setCriteriaDictionary($Metadata->criteria())
			->setOrderByDictionary($Metadata->order())
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
		$Metadata = Metadata::get($class);
		$subset = ($fetchSubset) ? $Metadata->fetchSubset($fetchSubset) : '*';
		$Query = (new Query($pdo))
			->on($Metadata->sql('source'), $subset)
			->setCriteriaDictionary($Metadata->criteria())
			->setOrderByDictionary($Metadata->order())
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
		$Metadata = Metadata::get($class);
		$subset = ($fetchSubset) ? $Metadata->fetchSubset($fetchSubset) : '*';
		$Query = (new Query($pdo))
			->on($Metadata->sql('source'), $subset)
			->setCriteriaDictionary($Metadata->criteria())
			->setOrderByDictionary($Metadata->order())
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
		$Metadata = Metadata::get($Entity);
		$data = DataMapper::object2sql($Entity);
		if($insertFn = $Metadata->sql('insertFn')) {
			$params = explode(',',str_replace(' ','',$insertFn));
			$procedure = array_shift($params);
			$Query = (new Query($pdo))->on($procedure);
			$execParams = [];
			foreach($params as $k){
				if($k[0]!='@') $execParams[$k] = $data[$k];
			}
			$pkeys = $Query->execCall($execParams);
			foreach($pkeys as $k=>$v) $Entity->$k = $v;
			return true;
		} else {
			$fields = implode(',', array_keys(array_filter($Metadata->properties(), function($p) { return !$p['readonly']; })));
			$Query = (new Query($pdo))->on($Metadata->sql('target'), $fields);
			if($Query->execInsert($data)==1) {
				// fetch AUTO ID
				if(count($Metadata->pkeys())==1 && isset($Metadata->properties()[$Metadata->pkeys()[0]]['autoincrement'])) {
					$k = $Metadata->pkeys()[0];
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
		$Metadata = Metadata::get($Entity);
		if($updateFn = $Metadata->sql('updateFn')) {
			$data = DataMapper::object2sql($Entity);
			$params = explode(',',str_replace(' ','',$updateFn));
			$procedure = array_shift($params);
			$Query = (new Query($pdo))->on($procedure);
			$execParams = [];
			foreach($params as $k){
				if($k[0]!='@') $execParams[$k] = $data[$k];
			}
			$Query->execCall($execParams);
			return true;
		} else {
			$data = DataMapper::object2sql($Entity, array_keys($changes));
			$fields = implode(',', array_keys($changes));
			$Query = (new Query($pdo))
				->on($Metadata->sql('target'), $fields)
				->criteriaExp($Metadata->pkCriteria($Entity));
			return in_array($Query->execUpdate($data), [0,1]) && $Query->errorCode()=='000000';
		}
	}
}
