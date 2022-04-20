<?php
namespace renovant\core\db\orm\util;
use renovant\core\db\orm\EntityTrait,
	renovant\core\util\Date,
	renovant\core\util\DateTime;
/**
 * ORM data hydrate helper
 * Helper class that hydrate/dehydrate Entity data.
 */
class DataMapper {

	/**
	 * Convert Entity from PHP object to data array.
	 */
	static function object2array(object $Entity, ?string $fetchSubset=null): array {
		$data = [];
		/** @var object|EntityTrait $Entity */
		foreach($Entity::metadata()->properties() as $k=>$v) {
			if($fetchSubset && !str_contains($Entity::metadata()->fetchSubset($fetchSubset), $k)) continue;
			$data[$k] = $Entity->$k;
		}
		return $data;
	}

	/**
	 * Convert Entity from PHP object to data array ready to JSON.
	 */
	static function object2json(object $Entity, ?string $fetchSubset=null): array {
		$data = [];
		/** @var object|EntityTrait $Entity */
		foreach($Entity::metadata()->properties() as $k=>$v) {
			if($fetchSubset && !str_contains($Entity::metadata()->fetchSubset($fetchSubset), $k)) continue;
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
	 */
	static function object2sql(object $Entity, array $changes=[]): array {
		$data = [];
		/** @var object|EntityTrait $Entity */
		foreach($Entity::metadata()->properties() as $k=>$v) {
			if($changes && !in_array($k, $changes)) continue;
			if($Entity::metadata()->property($k)['readonly']) continue;
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
	 * @throws \Exception
	 */
	static function sql2array(array $data, string $class): array {
		$props = call_user_func($class.'::metadata')->properties();
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
	 */
	static function sql2json(array $data, string $class): array {
		$props = call_user_func($class.'::metadata')->properties();
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
