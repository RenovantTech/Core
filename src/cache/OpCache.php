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
		trace(LOG_DEBUG, T_CACHE, '[INIT] OpCache directory: '.$DIR, null, $this->id);
		mkdir($DIR, 0755, true);
	}

	function get($id) {
		if(isset($this->cache[$id])) {
			trace(LOG_DEBUG, T_CACHE, '[MEM] '.$id, null, $this->id);
			return $this->cache[$id];
		} else {
			@include($this->_file($this->id, $id));
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
		return file_exists($this->_file($this->id, $id));
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
			$this->_write($this->id, $id, $value);
		}
		$this->cache[$id] = $value;
		return true;

	}

	function delete($id) {
		trace(LOG_DEBUG, T_CACHE, '[DELETE] '.$id, null, $this->id);
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

	static protected function _write($cache, $id, $value) {
		$data = var_export($value, true);
		$tmp = TMP_DIR.'/opc-'. md5($id);
		$file = substr(chunk_split(md5($id),8,'/'),0,-1);
		$f = explode('/', $file);
		mkdir(CACHE_DIR.'opc-'.$cache.'/'.$f[0]);
		mkdir(CACHE_DIR.'opc-'.$cache.'/'.$f[0].'/'.$f[1]);
		mkdir(CACHE_DIR.'opc-'.$cache.'/'.$f[0].'/'.$f[1].'/'.$f[2]);
		file_put_contents($tmp, '<?php $data='.$data.';', LOCK_EX);
		rename($tmp, CACHE_DIR.'opc-'.$cache.'/'.$file);
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
			trace(LOG_DEBUG, T_CACHE, '[STORE] BUFFER: '.count($buffer).' items on '.$k, null, __METHOD__);
			foreach($buffer as $data) {
				list($id, $value, $expire, $tags) = $data;
				self::_write($k, $id, $value);
			}
		}
	}
}
