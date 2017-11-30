<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\cache;
use const metadigit\core\{CACHE_DIR, TMP_DIR};
use const metadigit\core\trace\T_CACHE;
use function metadigit\core\trace;
/**
 * OpCache implementation of CacheInterface
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class OpCache implements CacheInterface {

	/** Write buffer
	 * @var array */
	static protected $buffer = [];

	/** ID (Cache Identifier)
	 * @var string */
	protected $id;
	/** Memory cache
	 * @var array */
	protected $cache = [];
	/** Cache directory
	 * @var string */
	protected $DIR;
	/** Write buffer
	 * @var boolean */
	protected $writeBuffer = false;

	/**
	 * @param string $id cache ID
	 * @param bool $writeBuffer write cache at shutdown
	 */
	function __construct($id, $writeBuffer=false) {
		$this->id = $id;
		$this->DIR = CACHE_DIR.'opc-'.$id.'/';
		$this->writeBuffer = (boolean) $writeBuffer;
		trace(LOG_DEBUG, T_CACHE, '[INIT] OpCache directory: '.$this->DIR, null, $this->id);
		mkdir($this->DIR, 0755, true);
	}

	function get($id) {
		if(isset($this->cache[$id])) {
			trace(LOG_DEBUG, T_CACHE, '[MEM] '.$id, null, $this->id);
			return $this->cache[$id];
		} else {
			@include($this->DIR.md5($id));
			$value = isset($data) ? $data: false;
			if($value===false) {
				trace(LOG_DEBUG, T_CACHE, '[MISSED] '.$id, null, $this->id);
				return false;
			}
			trace(LOG_DEBUG, T_CACHE, '[HIT] '.$id, null, $this->id);
			return $this->cache[$id] = $value;
		}
	}

	function has($id) {
		if(isset($this->cache[$id])) return true;
		return file_exists($this->DIR.md5($id));
	}

	function mget(array $ids) {
		// @TODO
	}

	function set($id, $value, $expire=null, $tags=null) {
		if($this->writeBuffer) {
			trace(LOG_DEBUG, T_CACHE, '[STORE] '.$id.' (buffered)', null, $this->id);
			self::$buffer[$this->id][] = [$id, $value, $expire, $tags];
		} else {
			trace(LOG_DEBUG, T_CACHE, '[STORE] '.$id, null, $this->id);
			$md5 = md5($id);
			$data = var_export($value, true);
			if(is_array($tags)) $tags = implode('|', $tags);
			$tmp = TMP_DIR.'/opc-'.$md5;
			file_put_contents($tmp, '<?php $data='.$data.';', LOCK_EX);
			rename($tmp, $this->DIR.$md5);
		}
		$this->cache[$id] = $value;
		return true;

	}

	function delete($id) {
		trace(LOG_DEBUG, T_CACHE, '[DELETE] '.$id, null, $this->id);
		if(isset($this->cache[$id])) unset($this->cache[$id]);
		return file_exists($this->DIR.md5($id)) ? unlink($this->DIR.md5($id)) : true;
	}

	function clean($mode=self::CLEAN_ALL, $tags=null) {
		$this->cache = [];
		unset(self::$buffer[$this->id]);
		switch($mode) {
			case self::CLEAN_ALL:
				self::_rmdir($this->DIR);


				break;
			case self::CLEAN_OLD:
				//@TODO
				break;
			case self::CLEAN_ALL_TAG:
				//@TODO
				break;
			case self::CLEAN_ANY_TAG:
				//@TODO
				break;
			case self::CLEAN_NOT_TAG:
				//@TODO
				break;
		}
		return true;
	}

	/**
	 * Recursive directory remove (like UNIX rm -fR /path)
	 * @param string $dir directory
	 */
	protected function _rmdir($dir) {
		$files = glob($dir .'/*');
		foreach ($files as $file) {
			is_dir($file) ? self::_rmdir($file) : unlink($file);
		}
	}

	/**
	 * Commit write buffer to directory on shutdown
	 */
	static function shutdown() {
		foreach(self::$buffer as $k=>$buffer) {
			trace(LOG_DEBUG, T_CACHE, '[STORE] BUFFER: '.count($buffer).' items on '.$k, null, __METHOD__);
			foreach($buffer as $data) {
				list($id, $value, $expire, $tags) = $data;
				if(is_array($tags)) $tags = implode('|', $tags);
				$md5 = md5($id);
				$data = var_export($value, true);
				if(is_array($tags)) $tags = implode('|', $tags);
				$tmp = TMP_DIR.'/opc-'.$md5;
				file_put_contents($tmp, '<?php $data='.$data.';', LOCK_EX);
				rename($tmp, CACHE_DIR.'opc-'.$k.'/'.$md5);
			}
		}
	}
}
