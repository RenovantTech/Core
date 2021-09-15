<?php
namespace renovant\core\cache;
interface CacheInterface {

	const CLEAN_ALL = 0;
	const CLEAN_OLD = 1;
	const CLEAN_ALL_TAG = 2;
	const CLEAN_ANY_TAG = 3;
	const CLEAN_NOT_TAG = 4;

	/**
	 * Check item existence
	 * @param string $id item ID
	 * @return boolean TRUE if ID exists into cache
	 */
	function has($id);

	/**
	 * Get a cached item
	 * @param string $id item ID
	 * @return mixed|false FALSE if cache missing
	 */
	function get($id);

	/**
	 * Store an item into cache
	 * @param string $id
	 * @param mixed $value
	 * @param int $expire
	 * @param mixed|null $tags tag (string) OR tags array
	 * @return boolean TRUE on success
	 */
	function set($id, $value, $expire=0, $tags=null);

	/**
	 * Remove an item from the cache..
	 * @param string $id item ID
	 * @return boolean TRUE on success
	 */
	function delete($id);

	/**
	 * Clean cache records
	 * @param integer $mode
	 * @param mixed $tags
	 * @return boolean TRUE on success
	 */
	function clean($mode=CacheInterface::CLEAN_ALL, $tags=null);
}
