<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\cache;
use const metadigit\core\trace\T_CACHE;
use metadigit\core\sys;
/**
 * Array implementation of CacheInterface, useful for testing.
 * ATTENTION: it's volatile, only in the current Request.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class ArrayCache implements CacheInterface {
	use \metadigit\core\CoreTrait;

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
