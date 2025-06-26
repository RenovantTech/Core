<?php
namespace renovant\core\db\orm;

use renovant\core\sys;
use renovant\core\db\orm\util\Metadata;
use renovant\core\util\{Date, DateTime};
use renovant\core\authz\{OrmAuthz, OrmTagsParser};

use const renovant\core\SYS_CACHE;

/**
 * Entity trait class must use to make ORM Repository work.
 */
trait EntityTrait {
	protected static array $_data;
	protected static OrmAuthz|false|null $OrmAuthz = false;
	protected static ?Metadata $Metadata           = null;

	/** @internal */
	public static function changes(object $Obj): array {
		$changes = [];
		foreach (self::$Metadata->properties() as $k => $meta) {
			if (!$meta['readonly'] && $Obj->$k !== self::$_data[spl_object_id($Obj)][$k]) {
				$changes[] = $k;
			}
		}
		return $changes;
	}

	/** @internal */
	public static function authz(): ?OrmAuthz {
		if (self::$OrmAuthz === false) {
			$k = str_replace('\\', '.', __CLASS__) . ':' . OrmAuthz::CACHE_TAG;
			if (false === $data = sys::cache(SYS_CACHE)->get($k)) {
				$data = OrmTagsParser::parse(__CLASS__);
				sys::cache(SYS_CACHE)->set($k, $data, 0, OrmAuthz::CACHE_TAG);
			}
			self::$OrmAuthz = $data;
		}
		return self::$OrmAuthz;
	}

	/** @internal */
	public static function metadata(): Metadata {
		if (!self::$Metadata) {
			$k = str_replace('\\', '.', __CLASS__) . ':' . Metadata::CACHE_TAG;
			if (!$data = sys::cache(SYS_CACHE)->get($k)) {
				$data = new Metadata(__CLASS__);
				sys::cache(SYS_CACHE)->set($k, $data, 0, Metadata::CACHE_TAG);
			}
			self::$Metadata = $data;
		}
		return self::$Metadata;
	}

	public function __construct(array $data = []) {
		$this->__invoke($data);
		if (method_exists($this, 'onInit')) {
			$this->onInit();
		}
		foreach (self::$Metadata->properties() as $k => $meta) {
			self::$_data[spl_object_id($this)][$k] = $this->$k;
		}
	}

	public function __destruct() {
		unset(self::$_data[spl_object_id($this)]);
	}

	public function __get($k) {
		return $this->$k;
	}

	public function __invoke(array $data = []) {
		foreach ($data as $k => $v) {
			$prop = self::$Metadata->property($k);
			if (!isset($prop)) {
				trigger_error('Undefined ORM metadata for property "' . $k . '", must have tag @orm', E_USER_ERROR);
			} elseif ($prop['null'] && (is_null($v) || $v === '')) {
				$v = null;
			} else {
				$v = match ($prop['type']) {
					'string'        => (string) $v,
					'integer'       => (int) $v,
					'float'         => (float) $v,
					'boolean'       => (bool) $v,
					'date'          => empty($v) ? null : (($v instanceof \DateTime) ? $v : new Date($v)),
					'datetime'      => empty($v) ? null : (($v instanceof \DateTime) ? $v : new DateTime($v)),
					'microdatetime' => empty($v) ? null : (($v instanceof \DateTime) ? $v : DateTime::createFromFormat('Y-m-d H:i:s.u', $v)),
					'object'        => (is_object($v)) ? $v : unserialize($v),
					'array'         => $v = (is_array($v)) ? $v : unserialize($v)
				};
			}
			$this->$k = $v;
		}
		return $this;
	}

	public function __set($k, $v) {
		$this([$k => $v]);
	}
}
