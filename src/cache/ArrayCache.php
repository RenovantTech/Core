<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\cache;
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
		$this->trace(LOG_DEBUG, TRACE_CACHE, __FUNCTION__, '[MISSED] '.$id);
		return false;
	}

	function has($id) {
		return isset($this->store[$id]);
	}

	function mget(array $ids) {
		$items = [];
		foreach($ids as $id) {
			if(isset($this->store[$id])) $items[$id] = $this->store[$id];
		}
		return $items;
	}

	function set($id, $value, $expire=0, $tag=null) {
		$this->trace(LOG_DEBUG, TRACE_CACHE, __FUNCTION__, '[STORE] '.$id);
		$this->store[$id] = $value;
		return true;
	}

	function delete($id) {
		$this->trace(LOG_DEBUG, TRACE_CACHE, __FUNCTION__, '[DELETE] '.$id);
		if(isset($this->store[$id])) {
			unset($this->store[$id]);
			return true;
		}
		return false;
	}

	function clean($mode=CacheInterface::CLEAN_ALL, $tags=null) {

	}
}