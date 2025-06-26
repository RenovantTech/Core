<?php
namespace renovant\core\db\orm\util;

use renovant\core\db\orm\EntityTrait;
use renovant\core\util\{Date, DateTime};

/** @internal */
class DataMapper {
	public static function object2array(object $Entity, ?string $fetchSubset = null): array {
		$data = [];
		/** @var object|EntityTrait $Entity */
		foreach ($Entity::metadata()->properties() as $k => $v) {
			if ($fetchSubset && !str_contains($Entity::metadata()->fetchSubset($fetchSubset), $k)) {
				continue;
			}
			$data[$k] = $Entity->$k;
		}
		return $data;
	}

	public static function object2json(object $Entity, ?string $fetchSubset = null): array {
		$data = [];
		/** @var object|EntityTrait $Entity */
		foreach ($Entity::metadata()->properties() as $k => $v) {
			if ($fetchSubset && !str_contains($Entity::metadata()->fetchSubset($fetchSubset), $k)) {
				continue;
			}
			$data[$k] = match ($v['type']) {
				'string','integer','float','boolean','time' => $Entity->$k,
				'date'          => (is_null($Entity->$k)) ? null : $Entity->$k->format('Y-m-d'),
				'datetime'      => (is_null($Entity->$k)) ? null : $Entity->$k->format(DateTime::W3C),
				'microdatetime' => (is_null($Entity->$k)) ? null : $Entity->$k->format('Y-m-d H:i:s.u'),
				'array'         => $Entity->$k,
				'object'        => json_encode($Entity->$k)
			};
		}
		return $data;
	}

	public static function object2sql(object $Entity, array $changes = []): array {
		$data = [];
		/** @var object|EntityTrait $Entity */
		foreach ($Entity::metadata()->properties() as $k => $v) {
			if ($changes && !in_array($k, $changes)) {
				continue;
			}
			if ($Entity::metadata()->property($k)['readonly']) {
				continue;
			}
			$data[$k] = match ($v['type']) {
				'string','integer','float','time' => $Entity->$k,
				'boolean'       => (int)$Entity->$k,
				'date'          => (is_null($Entity->$k)) ? null : $Entity->$k->format('Y-m-d'),
				'datetime'      => (is_null($Entity->$k)) ? null : $Entity->$k->format('Y-m-d H:i:s'),
				'microdatetime' => (is_null($Entity->$k)) ? null : $Entity->$k->format('Y-m-d H:i:s.u'),
				'array','object' => serialize($Entity->$k)
			};
		}
		return $data;
	}

	public static function sql2array(array $data, string $class): array {
		$props = call_user_func($class . '::metadata')->properties();
		foreach ($data as $k => &$v) {
			if (!isset($props[$k])) {
				continue;
			}
			if ($props[$k]['null'] && is_null($v)) {
				continue;
			}
			$v = match ($props[$k]['type']) {
				'string','time' => $v,
				'integer'       => (int) $v,
				'float'         => (float) $v,
				'boolean'       => (bool) $v,
				'date'          => new Date($v),
				'datetime'      => new DateTime($v),
				'microdatetime' => DateTime::createFromFormat('Y-m-d H:i:s.u', $v),
				'array','object' => unserialize($v)
			};
		}
		return $data;
	}

	public static function sql2json(array $data, string $class): array {
		$props = call_user_func($class . '::metadata')->properties();
		foreach ($data as $k => &$v) {
			if (!isset($props[$k])) {
				continue;
			}
			if ($props[$k]['null'] && is_null($v)) {
				continue;
			}
			$v = match ($props[$k]['type']) {
				'string','time','date' => $v,
				'integer'       => (int) $v,
				'float'         => (float) $v,
				'boolean'       => (bool) $v,
				'datetime'      => is_string($v) ? date(DateTime::W3C, strtotime($v)) : null,
				'microdatetime' => (string) $v,
				'array','object' => unserialize($v)
			};
		}
		return $data;
	}
}
