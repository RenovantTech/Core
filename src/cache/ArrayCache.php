<?php
namespace renovant\core\cache;
use const renovant\core\trace\T_CACHE;
use renovant\core\sys;
class ArrayCache implements CacheInterface {
	use \renovant\core\CoreTrait;

	/** Cache store
	 * @var array */
	protected $store = [];

	function get(string $id) {
		if(isset($this->store[$id])) return $this->store[$id];
		sys::trace(LOG_DEBUG, T_CACHE, '[MISSED] '.$id);
		return false;
	}

	function has(string $id): bool {
		return isset($this->store[$id]);
	}

	function set(string $id, mixed $value, int $expire=0, mixed $tag=null): bool {
		sys::trace(LOG_DEBUG, T_CACHE, '[STORE] '.$id);
		$this->store[$id] = $value;
		return true;
	}

	function delete(string $id): bool {
		sys::trace(LOG_DEBUG, T_CACHE, '[DELETE] '.$id);
		if(isset($this->store[$id])) {
			unset($this->store[$id]);
			return true;
		}
		return false;
	}

	function clean(int $mode=CacheInterface::CLEAN_ALL, $tags=null): bool {

	}
}
