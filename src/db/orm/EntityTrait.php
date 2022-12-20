<?php
namespace renovant\core\db\orm;
use const renovant\core\SYS_CACHE;
use renovant\core\sys,
	renovant\core\authz\OrmAuthz,
	renovant\core\authz\OrmTagsParser,
	renovant\core\db\orm\util\Metadata,
	renovant\core\util\Date,
	renovant\core\util\DateTime;
/**
 * Entity trait class must use to make ORM Repository work.
 */
trait EntityTrait {

	static protected array $_data;
	static protected OrmAuthz|false|null $OrmAuthz = false;
	static protected ?Metadata $Metadata = null;

	/** @internal */
	static function changes(object $Obj): array {
		$changes = [];
		foreach (self::$Metadata->properties() as $k => $meta)
			if(!$meta['readonly'] && $Obj->$k !== self::$_data[spl_object_id($Obj)][$k]) $changes[] = $k;
		return $changes;
	}

	/** @internal */
	static function authz(): ?OrmAuthz {
		if(self::$OrmAuthz === false) {
			$k = str_replace('\\','.',__CLASS__).':'.OrmAuthz::CACHE_TAG;
			if(false === $data = sys::cache(SYS_CACHE)->get($k)) {
				$data = OrmTagsParser::parse(__CLASS__);
				sys::cache(SYS_CACHE)->set($k, $data, 0, OrmAuthz::CACHE_TAG);
			}
			self::$OrmAuthz = $data;
		}
		return self::$OrmAuthz;
	}

	/** @internal */
	static function metadata(): Metadata {
		if(!self::$Metadata) {
			$k = str_replace('\\','.',__CLASS__).':'.Metadata::CACHE_TAG;
			if(!$data = sys::cache(SYS_CACHE)->get($k)) {
				$data = new Metadata(__CLASS__);
				sys::cache(SYS_CACHE)->set($k, $data, 0, Metadata::CACHE_TAG);
			}
			self::$Metadata = $data;
		}
		return self::$Metadata;
	}

	function __construct(array $data=[]) {
		$this->__invoke($data);
		if(method_exists($this, 'onInit')) $this->onInit();
		foreach (self::$Metadata->properties() as $k => $meta) {
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
			$prop = self::$Metadata->property($k);
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
