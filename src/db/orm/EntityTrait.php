<?php
namespace renovant\core\db\orm;
use const renovant\core\SYS_CACHE;
use renovant\core\sys,
	renovant\core\db\orm\util\Metadata,
	renovant\core\util\Date,
	renovant\core\util\DateTime;

/**
 * Entity trait class must use to make ORM Repository work.
 */
trait EntityTrait {

	static protected array $_data;
	static protected ?Metadata $_metadata = null;

	/**
	 * @internal
	 */
	static function changes(object $Obj): array {
		$changes = [];
		foreach (self::$_metadata->properties() as $k => $meta)
			if(!$meta['readonly'] && $Obj->$k !== self::$_data[spl_object_id($Obj)][$k]) $changes[] = $k;
		return $changes;
	}

	static function metadata(): Metadata {
		if(!self::$_metadata) {
			$k = str_replace('\\','.',__CLASS__).':'.Metadata::CACHE_TAG;
			if(!$data = sys::cache(SYS_CACHE)->get($k)) {
				$data = new Metadata(__CLASS__);
				sys::cache(SYS_CACHE)->set($k, $data, null, Metadata::CACHE_TAG);
			}
			self::$_metadata = $data;
		}
		return self::$_metadata;
	}

	function __construct(array $data=[]) {
		$this->__invoke($data);
		if(method_exists($this, 'onInit')) $this->onInit();
		foreach (self::$_metadata->properties() as $k => $meta) {
			self::$_data[spl_object_id($this)][$k] = $this->$k;
		}
	}

	function __destruct() {
		unset(self::$_data[spl_object_id($this)]);
	}

	function __get($k) {
		return $this->$k;
	}

	function __invoke(array $data=[]) {
		foreach($data as $k=>$v) {
			$prop = self::$_metadata->property($k);
			if(!isset($prop)) {
				trigger_error('Undefined ORM metadata for property "'.$k.'", must have tag @orm', E_USER_ERROR);
			} elseif ($prop['null'] && (is_null($v) || $v==='')) {
				$v = null;
			} else {
				switch($prop['type']) {
					case 'string': $v = (string) $v; break;
					case 'integer': $v = (int) $v; break;
					case 'float': $v = (float) $v; break;
					case 'boolean': $v = (bool) $v; break;
					case 'date': $v = empty($v) ? null : (($v instanceof \DateTime) ? $v : new Date($v)); break;
					case 'datetime': $v = empty($v) ? null : (($v instanceof \DateTime) ? $v : new DateTime($v)); break;
					case 'microdatetime': $v = empty($v) ? null : (($v instanceof \DateTime) ? $v : DateTime::createFromFormat('Y-m-d H:i:s.u', $v)); break;
					case 'object': $v = (is_object($v)) ? $v : unserialize($v); break;
					case 'array': $v = (is_array($v)) ? $v : unserialize($v); break;
				}
			}
			$this->$k = $v;
		}
		return $this;
	}

	function __set($k, $v) {
		$this([$k => $v]);
	}
}
