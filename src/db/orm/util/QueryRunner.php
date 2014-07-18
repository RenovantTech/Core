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
		$Query = (new Query($pdo, 2))->on($Metadata->sql('source'), '*')->setCriteriaDictionary($Metadata->criteria())->setOrderByDictionary($Metadata->order());
		$Query->criteriaExp($criteriaExp);
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
			$criteria = [];
			foreach($Metadata->pkeys() as $k)
				$criteria[] = sprintf('%s,EQ,%s', $k, $Entity->$k);
			$Query = (new Query($pdo, 2))->on($Metadata->sql('target'));
			$Query->criteriaExp(implode('|',$criteria))->criteriaExp($criteriaExp);
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
		$Query = (new Query($pdo, 2))->on($Metadata->sql('target'))->setCriteriaDictionary($Metadata->criteria())->setOrderByDictionary($Metadata->order());
		return $Query->orderByExp($orderExp)->criteriaExp($criteriaExp)->limit($limit)->execDelete();
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
	 * @return object|false
	 */
	static function fetchOne($pdo, $Metadata, $class, $offset, $orderExp, $criteriaExp, $fetchMode, $fetchSubset=null) {
		$subset = ($fetchSubset) ? implode(', ', $Metadata->subset()[$fetchSubset]) : '*';
		$Query = (new Query($pdo, 2))->on($Metadata->sql('source'), $subset)->setCriteriaDictionary($Metadata->criteria())->setOrderByDictionary($Metadata->order());
		if($data = $Query->orderByExp($orderExp)->criteriaExp($criteriaExp)->limit(1)->offset($offset)->execSelect()->fetch(\PDO::FETCH_ASSOC)) {
			switch($fetchMode) {
				case Repository::FETCH_OBJ:
					$Entity = DataMapper::sql2object($class, $data, $Metadata);
					break;
				case Repository::FETCH_ARRAY:
					$Entity = DataMapper::sql2array($data, $Metadata);
					break;
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
	static function fetchAll($pdo, $Metadata, $class, $offset, $limit, $orderExp, $criteriaExp, $fetchMode, $fetchSubset=null) {
		$subset = ($fetchSubset) ? implode(', ', $Metadata->subset()[$fetchSubset]) : '*';
		$Query = (new Query($pdo, 2))->on($Metadata->sql('source'), $subset)->setCriteriaDictionary($Metadata->criteria())->setOrderByDictionary($Metadata->order());
		$St = $Query->orderByExp($orderExp)->criteriaExp($criteriaExp)->limit($limit)->offset($offset)->execSelect();
		$entities = [];
		while($data = $St->fetch(\PDO::FETCH_ASSOC)) {
			switch($fetchMode) {
				case Repository::FETCH_OBJ:
					$entities[] = DataMapper::sql2object($class, $data, $Metadata);
					break;
				case Repository::FETCH_ARRAY:
					$entities[] = DataMapper::sql2array($data, $Metadata);
					break;
			}
		}
		return $entities;
	}

	/**
	 * @param string $pdo
	 * @param \metadigit\core\db\orm\Metadata $Metadata
	 * @param object $Entity
	 * @param string $subset
	 * @return void
	 */
	static function reFetch($pdo, $Metadata, $Entity, $subset=null) {
		$criteria = [];
		foreach($Metadata->pkeys() as $k)
			$criteria[] = sprintf('%s,EQ,%s', $k, $Entity->$k);
		$subset = ($subset) ? implode(', ', $Metadata->subset()[$subset]) : '*';
		$Query = (new Query($pdo, 2))->on($Metadata->sql('source'), $subset);
		$data = $Query->criteriaExp(implode('|',$criteria))->execSelect()->fetch(\PDO::FETCH_ASSOC);
		$data = DataMapper::sql2array($data, $Metadata);
		foreach($data as  $k=>$v) {
			$Entity->$k =$v;
		}
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
			$criteria = [];
			foreach($Metadata->pkeys() as $k)
				$criteria[] = sprintf('%s,EQ,%s', $k, $Entity->$k);
			$data = DataMapper::object2sql($Entity, $Metadata, array_keys($changes));
			$fields = implode(',', array_keys($changes));
			$Query = (new Query($pdo, 2))->on($Metadata->sql('target'), $fields);
			$Query->criteriaExp(implode('|',$criteria));
			return ($Query->execUpdate($data)==1 && $Query->errorCode()=='000000') ? true:false;
		}
	}
}