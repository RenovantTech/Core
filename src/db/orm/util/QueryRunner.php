<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\db\orm\util;
use metadigit\core\Kernel,
	metadigit\core\db\Query,
	metadigit\core\db\orm\Repository;
/**
 * ORM QueryRunner
 * Helper class that build & execute queries
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class QueryRunner {

	/**
	 * @param string $pdo
	 * @param \metadigit\core\db\orm\Metadata $Metadata
	 * @param string $criteriaExp
	 * @return int
	 */
	static function count($pdo, $Metadata, $criteriaExp=null) {
		$Query = (new Query($pdo, 2))
			->on($Metadata->sql('source'), '*')
			->setCriteriaDictionary($Metadata->criteria())
			->setOrderByDictionary($Metadata->order())
			->criteriaExp($criteriaExp);
		return (int) $Query->execCount();
	}

	/**
	 * @param string $pdo
	 * @param \metadigit\core\db\orm\Metadata $Metadata
	 * @param object $Entity
	 * @param string $criteriaExp
	 * @return boolean
	 */
	static function deleteOne($pdo, $Metadata, $Entity, $criteriaExp=null) {
		if($deleteFn = $Metadata->sql('deleteFn')) {
			$data = DataMapper::object2sql($Entity, $Metadata);
			$params = explode(',',str_replace(' ','',$deleteFn));
			$procedure = array_shift($params);
			$Query = (new Query($pdo, 2))->on($procedure, implode(',',$params));
			$execParams = [];
			foreach($params as $k){
				if($k[0]!='@') $execParams[$k] = $data[$k];
			}
			$Query->execCall($execParams);
			return true;
		} else {
			$Query = (new Query($pdo, 2))
				->on($Metadata->sql('target'))
				->criteriaExp($Metadata->pkCriteria($Entity))
				->criteriaExp($criteriaExp);
			return ($Query->execDelete()==1 && $Query->errorCode()=='000000') ? true:false;
		}
	}

	/**
	 * @param string $pdo
	 * @param \metadigit\core\db\orm\Metadata $Metadata
	 * @param integer $limit
	 * @param string $orderExp
	 * @param string $criteriaExp
	 * @return integer
	 */
	static function deleteAll($pdo, $Metadata, $limit, $orderExp, $criteriaExp) {
		$Query = (new Query($pdo, 2))
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
	 * @param \metadigit\core\db\orm\Metadata $Metadata
	 * @param string $class
	 * @param integer $offset
	 * @param string $orderExp
	 * @param string $criteriaExp
	 * @param int $fetchMode fetch mode: FETCH_OBJ, FETCH_ARRAY, FETCH_RAW
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return object|array|false
	 */
	static function fetchOne($pdo, $Metadata, $class, $offset, $orderExp, $criteriaExp, $fetchMode=null, $fetchSubset=null) {
		$subset = ($fetchSubset) ? $Metadata->fetchSubset($fetchSubset) : '*';
		$Query = (new Query($pdo, 2))
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
					$Entity = DataMapper::sql2array($data, $Metadata);
					break;
				case Repository::FETCH_JSON:
					$Entity = DataMapper::sql2json($data, $Metadata);
					break;
				default: // Repository::FETCH_OBJ
					$Entity = DataMapper::sql2object($class, $data, $Metadata);
			}
			return $Entity;
		} else return false;
	}

	/**
	 * @param string $pdo
	 * @param \metadigit\core\db\orm\Metadata $Metadata
	 * @param string $class
	 * @param integer $offset
	 * @param integer $limit
	 * @param string $orderExp
	 * @param string $criteriaExp
	 * @param int $fetchMode fetch mode: FETCH_OBJ, FETCH_ARRAY, FETCH_RAW
	 * @param string|null $fetchSubset optional fetch subset as defined in @orm-subset
	 * @return array
	 */
	static function fetchAll($pdo, $Metadata, $class, $offset, $limit, $orderExp, $criteriaExp, $fetchMode=null, $fetchSubset=null) {
		$subset = ($fetchSubset) ? $Metadata->fetchSubset($fetchSubset) : '*';
		$Query = (new Query($pdo, 2))
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
				case Repository::FETCH_OBJ:
					$entities[] = DataMapper::sql2object($class, $data, $Metadata);
					break;
				case Repository::FETCH_ARRAY:
					$entities[] = DataMapper::sql2array($data, $Metadata);
					break;
				default: // Repository::FETCH_JSON:
					$entities[] = DataMapper::sql2json($data, $Metadata);
			}
		}
		return $entities;
	}

	/**
	 * @param string $pdo
	 * @param \metadigit\core\db\orm\Metadata $Metadata
	 * @param object $Entity
	 * @return boolean
	 */
	static function insert($pdo, $Metadata, $Entity) {
		$data = DataMapper::object2sql($Entity, $Metadata);
		if($insertFn = $Metadata->sql('insertFn')) {
			$params = explode(',',str_replace(' ','',$insertFn));
			$procedure = array_shift($params);
			$Query = (new Query($pdo, 2))->on($procedure, implode(',',$params));
			$execParams = [];
			foreach($params as $k){
				if($k[0]!='@') $execParams[$k] = $data[$k];
			}
			$pkeys = $Query->execCall($execParams);
			foreach($pkeys as $k=>$v) $Entity->$k = $v;
			return true;
		} else {
			$fields = implode(',', array_keys(array_filter($Metadata->properties(), function($p) { return !$p['readonly']; })));
			$Query = (new Query($pdo, 2))->on($Metadata->sql('target'), $fields);
			if($Query->execInsert($data)==1) {
				// fetch AUTO ID
				if(count($Metadata->pkeys())==1 && isset($Metadata->properties()[$Metadata->pkeys()[0]]['autoincrement'])) {
					$k = $Metadata->pkeys()[0];
					$v = (int)Kernel::pdo($pdo)->lastInsertId();
					$Entity->$k =$v;
				}
				return true;
			} else return false;
		}
	}

	/**
	 * @param string $pdo
	 * @param \metadigit\core\db\orm\Metadata $Metadata
	 * @param object $Entity
	 * @param array $changes
	 * @return boolean
	 */
	static function update($pdo, $Metadata, $Entity, $changes) {
		if($updateFn = $Metadata->sql('updateFn')) {
			$data = DataMapper::object2sql($Entity, $Metadata);
			$params = explode(',',str_replace(' ','',$updateFn));
			$procedure = array_shift($params);
			$Query = (new Query($pdo, 2))->on($procedure, implode(',',$params));
			$execParams = [];
			foreach($params as $k){
				if($k[0]!='@') $execParams[$k] = $data[$k];
			}
			$Query->execCall($execParams);
			return true;
		} else {
			$data = DataMapper::object2sql($Entity, $Metadata, array_keys($changes));
			$fields = implode(',', array_keys($changes));
			$Query = (new Query($pdo, 2))
				->on($Metadata->sql('target'), $fields)
				->criteriaExp($Metadata->pkCriteria($Entity));
			return ($Query->execUpdate($data)==1 && $Query->errorCode()=='000000') ? true:false;
		}
	}
}
