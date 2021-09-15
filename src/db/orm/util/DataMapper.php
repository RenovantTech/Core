<?php
namespace renovant\core\db\orm\util;
use renovant\core\db\orm\Repository,
	renovant\core\util\Date,
	renovant\core\util\DateTime;
/**
 * ORM data hydrate helper
 * Helper class that hydrate/dehydrate Entity data.
 */
class DataMapper {

	/**
	 * Convert Entity from PHP object to data array.
	 * @param object $Entity
	 * @param string|null $fetchSubset
	 * @return array
	 */
	static function object2array(object $Entity, $fetchSubset=null) {
		$data = [];
		foreach($Entity::metadata(Repository::META_PROPS) as $k=>$v) {
			if($fetchSubset && strstr($Entity::metadata(Repository::META_FETCH_SUBSETS, $fetchSubset), $k)===false) continue;
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
	static function object2json(object $Entity, $fetchSubset=null) {
		$data = [];
		foreach($Entity::metadata(Repository::META_PROPS) as $k=>$v) {
			if($fetchSubset && strstr($Entity::metadata(Repository::META_FETCH_SUBSETS, $fetchSubset),$k)===false) continue;
			switch($v['type']) {
				case 'string':
				case 'integer':
				case 'float':
				case 'boolean':
				case 'time':
					$data[$k] = $Entity->$k;
					break;
				case 'date': $data[$k] = (is_null($Entity->$k)) ? null : $Entity->$k->format('Y-m-d'); break;
				case 'datetime': $data[$k] = (is_null($Entity->$k)) ? null : $Entity->$k->format(DateTime::W3C); break;
				case 'microdatetime': $data[$k] = (is_null($Entity->$k)) ? null : $Entity->$k->format('Y-m-d H:i:s.u'); break;
				case 'array':
				case 'object': $data[$k] = serialize($Entity->$k); break;
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
	static function object2sql(object $Entity, array $changes=[]) {
		$data = [];
		foreach($Entity::metadata(Repository::META_PROPS) as $k=>$v) {
			if($changes && !in_array($k, $changes)) continue;
			if($Entity::metadata(Repository::META_PROPS)[$k]['readonly']) continue;
			switch($v['type']) {
				case 'string':
				case 'integer':
				case 'float':
				case 'time':
					$data[$k] = $Entity->$k;
					break;
				case 'boolean': $data[$k] = (int)$Entity->$k; break;
				case 'date': $data[$k] = (is_null($Entity->$k)) ? null : $Entity->$k->format('Y-m-d'); break;
				case 'datetime': $data[$k] = (is_null($Entity->$k)) ? null : $Entity->$k->format('Y-m-d H:i:s'); break;
				case 'microdatetime': $data[$k] = (is_null($Entity->$k)) ? null : $Entity->$k->format('Y-m-d H:i:s.u'); break;
				case 'array':
				case 'object': $data[$k] = serialize($Entity->$k); break;
			}
		}
		return $data;
	}

	/**
	 * Inject SQL types into array data, converting to proper PHP types.
	 * @param array $data
	 * @param string $class
	 * @return array
	 * @throws \Exception
	 */
	static function sql2array(array $data, string $class) {
		$props = call_user_func($class.'::metadata', Repository::META_PROPS);
		foreach($data as $k=>&$v) {
			if(!isset($props[$k])) continue;
			if($props[$k]['null'] && is_null($v)) continue;
			switch($props[$k]['type']) {
				case 'string':
				case 'time': break;
				case 'integer': $v = (int) $v; break;
				case 'float': $v = (float) $v; break;
				case 'boolean': $v = (bool) $v; break;
				case 'date': $v = new Date($v); break;
				case 'datetime': $v = new DateTime($v); break;
				case 'microdatetime': $v = DateTime::createFromFormat('Y-m-d H:i:s.u', $v); break;
				case 'array':
				case 'object': $v = unserialize($v); break;
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
	static function sql2json(array $data, string $class) {
		$props = call_user_func($class.'::metadata', Repository::META_PROPS);
		foreach($data as $k=>&$v) {
			if(!isset($props[$k])) continue;
			if($props[$k]['null'] && is_null($v)) continue;
			switch($props[$k]['type']) {
				case 'date':
				case 'time':
				case 'string': break;
				case 'integer': $v = (int) $v; break;
				case 'float': $v = (float) $v; break;
				case 'boolean': $v = (bool) $v; break;
				case 'datetime': $v = date(DateTime::W3C, strtotime($v)); break;
				case 'microdatetime': $v = (string) $v; break;
				case 'array':
				case 'object': $v = unserialize($v); break;
			}
		}
		return $data;
	}
}
