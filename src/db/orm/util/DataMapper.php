<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\db\orm\util;
use metadigit\core\db\orm\Metadata,
	metadigit\core\util\DateTime;
/**
 * ORM data hydrate helper
 * Helper class that hydrate/dehydrate Entity data.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class DataMapper {

	/**
	 * Convert Entity from PHP object to data array.
	 * @param object $Entity
	 * @param string|null $fetchSubset
	 * @return array
	 */
	static function object2array($Entity, $fetchSubset=null) {
		$Metadata = Metadata::get($Entity);
		$prop = $Metadata->properties();
		$data = [];
		foreach($prop as $k=>$v) {
			if($fetchSubset && strstr($Metadata->fetchSubset($fetchSubset),$k)===false) continue;
			$data[$k] = $Entity->$k;
		}
		return $data;
	}

	/**
	 * Convert Entity from PHP object to data array ready to JSON.
	 * @param object $Entity
	 * @param string|null $fetchSubset
	 * @return array
	 */
	static function object2json($Entity, $fetchSubset=null) {
		$Metadata = Metadata::get($Entity);
		$prop = $Metadata->properties();
		$data = [];
		foreach($prop as $k=>$v) {
			if($fetchSubset && strstr($Metadata->fetchSubset($fetchSubset),$k)===false) continue;
			switch($v['type']) {
				case 'string':
				case 'integer':
				case 'float':
				case 'boolean':
					$data[$k] = $Entity->$k;
					break;
				case 'date': $data[$k] = (is_null($Entity->$k)) ? null : $Entity->$k->format('Y-m-d'); break;
				case 'datetime': $data[$k] = (is_null($Entity->$k)) ? null : $Entity->$k->format(DateTime::W3C); break;
				case 'object': $data[$k] = serialize($Entity->$k); break;
				case 'array': $data[$k] = serialize($Entity->$k); break;
			}
		}
		return $data;
	}

	/**
	 * Convert Entity from PHP object to proper SQL types array.
	 * @param object $Entity
	 * @param array $changes changed values on update
	 * @return array
	 */
	static function object2sql($Entity, array $changes=[]) {
		$prop = Metadata::get($Entity)->properties();
		$data = [];
		foreach($prop as $k=>$v) {
			if($changes && !in_array($k, $changes)) continue;
			if($prop[$k]['readonly']) continue;
			switch($v['type']) {
				case 'string':
				case 'integer':
				case 'float':
					$data[$k] = $Entity->$k;
					break;
				case 'boolean': $data[$k] = (int)$Entity->$k; break;
				case 'date': $data[$k] = (is_null($Entity->$k)) ? null : $Entity->$k->format('Y-m-d'); break;
				case 'datetime': $data[$k] = (is_null($Entity->$k)) ? null : $Entity->$k->format('Y-m-d H:i:s'); break;
				case 'object': $data[$k] = serialize($Entity->$k); break;
				case 'array': $data[$k] = serialize($Entity->$k); break;
			}
		}
		return $data;
	}

	/**
	 * Inject SQL types into array data, converting to proper PHP types.
	 * @param array $data
	 * @param string $class
	 * @return array
	 */
	static function sql2array(array $data, $class) {
		$prop = Metadata::get($class)->properties();
		foreach($data as $k=>&$v) {
			if(!isset($prop[$k])) {
				trigger_error('Undefined ORM metadata for property "'.$k.'", must have tag @orm', E_USER_ERROR);
				continue;
			}
			if($prop[$k]['null'] && is_null($v)) continue;
			switch($prop[$k]['type']) {
				case 'string': break;
				case 'integer': $v = (int) $v; break;
				case 'float': $v = (float) $v; break;
				case 'boolean': $v = (bool) $v; break;
				case 'date': $v = new DateTime($v); break;
				case 'datetime': $v = new DateTime($v); break;
				case 'object': $v = unserialize($v); break;
				case 'array': $v = unserialize($v); break;
			}
		}
		return $data;
	}

	/**
	 * Inject SQL types into array data ready for JSON conversion, converting to proper PHP types.
	 * @param array $data
	 * @param string $class
	 * @return array
	 */
	static function sql2json(array $data, $class) {
		$prop = Metadata::get($class)->properties();
		foreach($data as $k=>&$v) {
			if(!isset($prop[$k])) {
				trigger_error('Undefined ORM metadata for property "'.$k.'", must have tag @orm', E_USER_ERROR);
				continue;
			}
			if($prop[$k]['null'] && is_null($v)) continue;
			switch($prop[$k]['type']) {
				case 'string': break;
				case 'integer': $v = (int) $v; break;
				case 'float': $v = (float) $v; break;
				case 'boolean': $v = (bool) $v; break;
				case 'date': break;
				case 'datetime': $v = date(DateTime::W3C, strtotime($v)); break;
				case 'object': $v = unserialize($v); break;
				case 'array': $v = unserialize($v); break;
			}
		}
		return $data;
	}
}
