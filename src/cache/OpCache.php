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
use metadigit\core\sys;
/**
 * OpCache implementation of CacheInterface
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class OpCache implements CacheInterface {
	use \metadigit\core\CoreTrait;

	/** Write buffer
	 * @var array */
	static protected $buffer = [];

	/** ID (Cache Identifier)
	 * @var string */
	protected $id;
	/** Memory cache
	 * @var array */
	protected $cache = [];
	/** Write buffer
	 * @var boolean */
	protected $writeBuffer = false;

	/**
	 * @param string $id cache ID
	 * @param bool $writeBuffer write cache at shutdown
	 */
	function __construct($id, $writeBuffer=false) {
		$this->id = $id;
		$DIR = CACHE_DIR.'opc-'.$id.'/';
		$this->writeBuffer = (boolean) $writeBuffer;
		sys::trace(LOG_DEBUG, T_CACHE, '[INIT] OpCache directory: '.$DIR, null, $this->_);
		if(!file_exists($DIR))
			mkdir($DIR, 0755, true);
	}

	function get($id) {
		if(isset($this->cache[$id])) {
			sys::trace(LOG_DEBUG, T_CACHE, '[MEM] '.$id, null, $this->_);
			return $this->cache[$id];
		} else {
			if(file_exists($file = $this->_file($this->id, $id))) include($file);
			if(!isset($data) || (isset($expire) && ($expire!=0 && $expire<time()))) {
				sys::trace(LOG_DEBUG, T_CACHE, '[MISSED] '.$id, null, $this->_);
				return false;
			}
			sys::trace(LOG_DEBUG, T_CACHE, '[HIT] '.$id, null, $this->_);
			return $this->cache[$id] = $data;
		}
	}

	function has($id) {
		if(isset($this->cache[$id])) return true;
		return file_exists($this->_file($this->id, $id));
	}

	function set($id, $value, $expire=null, $tags=null) {
		if($this->writeBuffer) {
			sys::trace(LOG_DEBUG, T_CACHE, '[STORE] '.$id.' (buffered)', null, $this->_);
			self::$buffer[$this->id][] = [$id, $value, $expire, $tags];
		} else {
			sys::trace(LOG_DEBUG, T_CACHE, '[STORE] '.$id, null, $this->_);
			$this->_write($this->id, $id, $value, $expire);
		}
		$this->cache[$id] = $value;
		return true;

	}

	function delete($id) {
		sys::trace(LOG_DEBUG, T_CACHE, '[DELETE] '.$id, null, $this->_);
		if(isset($this->cache[$id])) unset($this->cache[$id]);
		$file = $this->_file($this->id, $id);
		return file_exists($file) ? unlink($file) : true;
	}

	function clean($mode=self::CLEAN_ALL, $tags=null) {
		$this->cache = [];
		unset(self::$buffer[$this->id]);
		switch($mode) {
			case self::CLEAN_ALL:
				self::_clean(CACHE_DIR.'opc-'.$this->id);
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

	static protected function _file($cache, $id) {
		return CACHE_DIR.'opc-'.$cache.'/'.substr(chunk_split(md5($id),8,'/'),0,-1);
	}

	static protected function _write($cache, $id, $value, $expire) {
		$data = (is_object($value)) ? 'unserialize(\''.serialize($value).'\')' : var_export($value, true);
		$tmp = TMP_DIR.'/opc-'. md5($id);
		$file = CACHE_DIR.'opc-'.$cache.'/'.substr(chunk_split(md5($id),8,'/'),0,-1);
		if(!file_exists($dir = substr($file, 0, -9)))
			mkdir($dir, 0755, true);
		file_put_contents($tmp, '<?php $expire='.(int)$expire.'; $data='.$data.';', LOCK_EX);
		rename($tmp, $file);
	}

	/**
	 * Recursive directory remove (like UNIX rm -fR /path)
	 * @param string $dir directory
	 */
	static protected function _clean($dir) {
		$files = glob($dir .'/*');
		foreach ($files as $file) {
			is_dir($file) ? self::_clean($file) : unlink($file);
		}
	}

	/**
	 * Commit write buffer to directory on shutdown
	 */
	static function shutdown() {
		foreach(self::$buffer as $k=>$buffer) {
			sys::trace(LOG_DEBUG, T_CACHE, '[STORE] BUFFER: '.count($buffer).' items on '.$k, null, __METHOD__);
			foreach($buffer as $data) {
				list($id, $value, $expire, $tags) = $data;
				self::_write($k, $id, $value, $expire);
			}
		}
	}
}
register_shutdown_function(__NAMESPACE__.'\OpCache::shutdown');
