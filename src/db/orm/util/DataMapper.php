<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\db\orm\util;
use metadigit\core\util\DateTime;
/**
 * ORM data hydrator
 * Helper class that hydrate/dehydrate Entity data.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class DataMapper {

	/**
	 * Inject array into Entity data, converting to proper PHP types.
	 * @param mixed $EntityOrClass Entity or class
	 * @param array $data
	 * @param \metadigit\core\db\orm\Metadata $Metadata
	 * @return object
	 */
	static function array2object($EntityOrClass, array $data, $Metadata) {
		$prop = $Metadata->properties();
		foreach($data as $k=>&$v) {
			if(!isset($prop[$k])) {
				trigger_error('Undefined ORM metadata for property "'.$k.'", must have tag @orm', E_USER_ERROR);
				continue;
			}
			if($prop[$k]['null'] && (is_null($v) || $v=='')) {
				$v = null;
				continue;
			}
			switch($prop[$k]['type']) {
				case 'string': break;
				case 'integer': $v = (int) $v; break;
				case 'float': $v = (float) $v; break;
				case 'boolean': $v = (bool) $v; break;
				case 'date': $v = ($v instanceof \DateTime) ? $v : new DateTime($v); break;
				case 'datetime': $v = ($v instanceof \DateTime) ? $v : new DateTime($v); break;
				case 'object': $v = (is_object($v)) ? $v : unserialize($v); break;
				case 'array': $v = (is_array($v)) ? $v : unserialize($v); break;
			}
		}
		return (is_object($EntityOrClass)) ? $EntityOrClass->__construct($data) : new $EntityOrClass($data);
	}

	/**
	 * Convert Entity from PHP object to data array.
	 * @param object $Entity
	 * @param \metadigit\core\db\orm\Metadata $Metadata
	 * @param string|null $subset
	 * @return array
	 */
	static function object2array($Entity, $Metadata, $subset=null) {
		$prop = $Metadata->properties();
		$data = [];
		foreach($prop as $k=>$v) {
			if($subset && !in_array($k, $Metadata->subset()[$subset])) continue;
			switch($v['type']) {
				case 'string':
				case 'integer':
				case 'float':
				case 'boolean':
					$data[$k] = $Entity->$k;
					break;
				case 'date': $data[$k] = (is_null($Entity->$k)) ? null : $Entity->$k->format('Y-m-d'); break;
				case 'datetime': $data[$k] = (is_null($Entity->$k)) ? null : $Entity->$k->format('Y-m-d H:i:s'); break;
				case 'object': $data[$k] = serialize($Entity->$k); break;
				case 'array': $data[$k] = serialize($Entity->$k); break;
			}
		}
		return $data;
	}

	/**
	 * Convert Entity from PHP object to proper SQL types array.
	 * @param object $Entity
	 * @param \metadigit\core\db\orm\Metadata $Metadata
	 * @param array $changes changed values on update
	 * @return array
	 */
	static function object2sql($Entity, $Metadata, array $changes=[]) {
		$prop = $Metadata->properties();
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
	 * @param \metadigit\core\db\orm\Metadata $Metadata
	 * @return array
	 */
	static function sql2array(array $data, $Metadata) {
		$prop = $Metadata->properties();
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
	 * Inject SQL types into Entity data, converting to proper PHP types.
	 * @param string $class Entity class
	 * @param array $data
	 * @param \metadigit\core\db\orm\Metadata $Metadata
	 * @return object
	 */
	static function sql2object($class, array $data, $Metadata) {
		$prop = $Metadata->properties();
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
		return new $class($data);
	}
}
