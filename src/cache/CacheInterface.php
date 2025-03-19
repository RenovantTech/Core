<?php
namespace renovant\core\cache;

interface CacheInterface {
	public const CLEAN_ALL     = 0;
	public const CLEAN_OLD     = 1;
	public const CLEAN_ALL_TAG = 2;
	public const CLEAN_ANY_TAG = 3;
	public const CLEAN_NOT_TAG = 4;

	/**
	 * Check item existence
	 * @param string $id item ID
	 * @return boolean TRUE if ID exists into cache
	 */
	public function has(string $id): bool;

	/**
	 * Get a cached item
	 * @param string $id item ID
	 * @return mixed|false FALSE if cache missing
	 */
	public function get(string $id);

	/**
	 * Store an item into cache
	 * @param string $id
	 * @param mixed $value
	 * @param int $expire
	 * @param mixed|null $tags tag (string) OR tags array
	 * @return boolean TRUE on success
	 */
	public function set(string $id, mixed $value, int $expire = 0, mixed $tags = null): bool;

	/**
	 * Remove an item from the cache..
	 * @param string $id item ID
	 * @return boolean TRUE on success
	 */
	public function delete(string $id): bool;

	/**
	 * Clean cache records
	 * @param integer $mode
	 * @param mixed $tags
	 * @return boolean TRUE on success
	 */
	public function clean(int $mode = CacheInterface::CLEAN_ALL, $tags = null): bool;
}
