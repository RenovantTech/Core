<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\cache;
use const renovant\core\trace\T_CACHE;
use renovant\core\sys;
/**
 * Array implementation of CacheInterface, useful for testing.
 * ATTENTION: it's volatile, only in the current Request.
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
class ArrayCache implements CacheInterface {
	use \renovant\core\CoreTrait;

	/** Cache store
	 * @var array */
	protected $store = [];

	function get($id) {
		if(isset($this->store[$id])) return $this->store[$id];
		sys::trace(LOG_DEBUG, T_CACHE, '[MISSED] '.$id);
		return false;
	}

	function has($id) {
		return isset($this->store[$id]);
	}

	function set($id, $value, $expire=0, $tag=null) {
		sys::trace(LOG_DEBUG, T_CACHE, '[STORE] '.$id);
		$this->store[$id] = $value;
		return true;
	}

	function delete($id) {
		sys::trace(LOG_DEBUG, T_CACHE, '[DELETE] '.$id);
		if(isset($this->store[$id])) {
			unset($this->store[$id]);
			return true;
		}
		return false;
	}

	function clean($mode=CacheInterface::CLEAN_ALL, $tags=null) {

	}
}
