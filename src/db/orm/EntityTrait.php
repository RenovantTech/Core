<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\db\orm;
use const renovant\core\SYS_CACHE;
use renovant\core\sys,
	renovant\core\db\orm\util\MetadataParser,
	renovant\core\util\Date,
	renovant\core\util\DateTime;

/**
 * @internal
 * @param $class
 * @return false|mixed|void
 * @throws Exception
 * @throws \ReflectionException
 */
function metadataFetch($class) {
	$k = str_replace('\\','.',$class).':orm:metadata';
	if(!$data = sys::cache(SYS_CACHE)->get($k)) {
		$data = MetadataParser::parse($class);
		sys::cache(SYS_CACHE)->set($k, $data, null, 'orm:metadata');
	}
	return $data;
}

/**
 * Entity trait class must use to make ORM Repository work.
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
trait EntityTrait {

	static protected array $_metadata;

	/**
	 * @param string|null $k
	 * @param mixed|null $param
	 * @return mixed|void
	 * @throws Exception
	 * @throws \ReflectionException
	 */
	static function metadata(?string $k=null, $param=null) {
		if(empty(self::$_metadata)) self::$_metadata = metadataFetch(__CLASS__);

		switch ($k) {
			case null:
				return;
			case Repository::META_EVENTS:
				return in_array($param, self::$_metadata[Repository::META_EVENTS]);
			case Repository::META_FETCH_SUBSETS:
				if(isset(self::$_metadata[Repository::META_FETCH_SUBSETS][$param])) return self::$_metadata[Repository::META_FETCH_SUBSETS][$param];
				trigger_error('Invalid FETCH SUBSET requested: '.$param, E_USER_WARNING);
				return '*';
			case Repository::META_PKCRITERIA:
				if(is_object($param)) {
					$keys = [];
					foreach(self::$_metadata[Repository::META_PKEYS] as $k) $keys[] = $param->$k;
				} else $keys = $param;
				return preg_replace(array_fill(0, count(self::$_metadata[Repository::META_PKEYS]), '/\?/'), $keys, self::$_metadata[Repository::META_PKCRITERIA], 1);
			case Repository::META_PROPS:
				if($param) {
					if(isset(self::$_metadata[Repository::META_PROPS][$param]))
						return self::$_metadata[Repository::META_PROPS][$param];
					else
						trigger_error('Undefined ORM metadata for property "'.$param.'", must have tag @orm', E_USER_WARNING);
					return;
				}
				else return self::$_metadata[Repository::META_PROPS];
			case Repository::META_VALIDATE_SUBSETS:
				if(isset(self::$_metadata[Repository::META_VALIDATE_SUBSETS][$param])) return explode(',', self::$_metadata[Repository::META_VALIDATE_SUBSETS][$param]);
				trigger_error('Invalid VALIDATE SUBSET requested: '.$param, E_USER_WARNING);
				return array_keys(self::$_metadata[Repository::META_PROPS]);
			default:
				return ($param) ? self::$_metadata[$k][$param]??null : self::$_metadata[$k];
		}
	}

	private array $_changes = [];

	/**
	 * Entity constructor
	 * @param array $data Entity data
	 */
	function __construct(array $data=[]) {
		$this->__invoke($data);
		$this->onInit();
	}

	function __get($k) {
		return $this->$k;
	}

	function __invoke(array $data=[]) {
		foreach($data as $k=>$v) {
			if(!isset(self::$_metadata[Repository::META_PROPS][$k])) {
				trigger_error('Undefined ORM metadata for property "'.$k.'", must have tag @orm', E_USER_ERROR);
			} elseif (self::$_metadata[Repository::META_PROPS][$k]['null'] && (is_null($v) || $v==='')) {
				$v = null;
			} else {
				switch(self::$_metadata[Repository::META_PROPS][$k]['type']) {
					case 'string': $v = (string) $v; break;
					case 'integer': $v = (int) $v; break;
					case 'float': $v = (float) $v; break;
					case 'boolean': $v = (bool) $v; break;
					case 'date': $v = ($v instanceof \DateTime) ? $v : new Date($v); break;
					case 'datetime': $v = ($v instanceof \DateTime) ? $v : new DateTime($v); break;
					case 'object': $v = (is_object($v)) ? $v : unserialize($v); break;
					case 'array': $v = (is_array($v)) ? $v : unserialize($v); break;
				}
			}
//			if($this->$k !== $v) $this->_changes[] = $k;
			$this->$k = $v;
		}
	}

	function __set($k, $v) {
		$this([$k => $v]);
	}

	protected function onInit() {}
	protected function onSave() {}
	protected function onDelete() {}

	private function _changes(): array {
		return $this->_changes;
	}
	private function _isDirty(): bool {
		return !empty($this->_changes);
	}
}
