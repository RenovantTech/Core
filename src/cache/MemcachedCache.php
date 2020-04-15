<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\cache;
use const renovant\core\trace\{T_CACHE,T_ERROR};
use renovant\core\sys;
/**
 * Sqlite implementation of CacheInterface
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
class MemcachedCache implements CacheInterface {
	use \renovant\core\CoreTrait;

	const DEFAULT_PARAMS = ['localhost', 11211, 0];

	/** Write buffer
	 * @var array */
	static protected $buffer = [];
	/** Memory cache
	 * @var array */
	protected $cache = [];
	/** Memory cache
	 * @var array */
	protected $params = self::DEFAULT_PARAMS;
	/** Write buffer
	 * @var boolean */
	protected $writeBuffer = false;
	/** SQLite3 resource (READ only)
	 * @var \Memcached */
	protected $Memcached;

	/**
	 * @param string $params servers params
	 * @param bool $writeBuffer write cache at shutdown
	 */
	function __construct($params=null, $writeBuffer=false) {
		$this->params = $params || self::DEFAULT_PARAMS;
		$this->writeBuffer = (boolean) $writeBuffer;
		$this->__wakeup();
	}

	function __sleep() {
		return ['_', 'params', 'writeBuffer'];
	}

	function __wakeup() {
		sys::trace(LOG_DEBUG, T_CACHE, '[INIT] Memcached', null, $this->_);
		try {
			$this->Memcached = new \Memcached($this->_);
			$this->Memcached->setOption(\Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
			$this->Memcached->setOption(\Memcached::OPT_NO_BLOCK, true);
			$this->Memcached->setOption(\Memcached::OPT_TCP_NODELAY, true);
			$this->Memcached->setOption(\Memcached::OPT_RETRY_TIMEOUT, 1);
			if(empty($this->Memcached->getServerList())) {
				if(is_array($this->params[0])) $this->Memcached->addServers($this->params);
				else $this->Memcached->addServer($this->params[0], $this->params[1], $this->params[2]);
			}
		}  catch(\Exception $Ex) {
			sys::trace(LOG_ERR, T_ERROR, '[INIT] FAILURE', null, $this->_);
		}
	}

	function get($id) {
		if(isset($this->cache[$id])) {
			sys::trace(LOG_DEBUG, T_CACHE, '[MEM] ' . $id, null, $this->_);
			return $this->cache[$id];
		}
		try {
			if($this->Memcached) {
				$data = $this->Memcached->get($id);
				if($data) {
					sys::trace(LOG_DEBUG, T_CACHE, '[HIT] '.$id, null, $this->_);
					return $this->cache[$id] = $data['v'];
				}
			}
			sys::trace(LOG_DEBUG, T_CACHE, '[MISSED] '.$id, null, $this->_);
			return false;
		} catch(\Exception $Ex) {
			sys::trace(LOG_ERR, T_ERROR, '[GET] '.$id.' FAILURE', null, $this->_);
			return false;
		}
	}

	function has($id) {
		if(isset($this->cache[$id]))
			return true;
		try {
			if($this->Memcached) {
				$this->Memcached->get($id);
				return !($this->Memcached->getResultCode() == \Memcached::RES_NOTFOUND);
			}
			return false;
		} catch(\Exception $Ex) {
			sys::trace(LOG_ERR, T_ERROR, '[HAS] '.$id.' FAILURE', null, $this->_);
			return false;
		}
	}

	function set($id, $value, $expire=0, $tags=null) {
		try {
			if($this->writeBuffer) {
				sys::trace(LOG_DEBUG, T_CACHE, '[STORE] '.$id.' (buffered)', null, $this->_);
				self::$buffer[$this->_]['params'] = $this->params;
				self::$buffer[$this->_]['values'][$id] = [$value, $expire, $tags];
			} else {
				sys::trace(LOG_DEBUG, T_CACHE, '[STORE] '.$id, null, $this->_);
				if(is_array($tags)) $tags = implode('|', $tags);
				if(false === $this->Memcached->set($id, ['v'=>$value, 'tags'=>$tags], $expire))
					throw new \Exception();
			}
			$this->cache[$id] = $value;
			return true;
		} catch(\Exception $Ex) {
			sys::trace(LOG_ERR, T_ERROR, '[STORE] '.$id.' FAILURE', null, $this->_);
			return false;
		}
	}

	function delete($id) {
		if(isset($this->cache[$id])) {
			$this->cache[$id] = null;
			unset($this->cache[$id]);
		}
		try {
			sys::trace(LOG_DEBUG, T_CACHE, '[DELETE] '.$id, null, $this->_);
			if($this->writeBuffer && isset(self::$buffer[$this->_]) && isset(self::$buffer[$this->_]['values'][$id])) {
				unset(self::$buffer[$this->_]['values'][$id]);
				return true;
			} else return $this->Memcached->delete($id);
		} catch(\Exception $Ex) {
			sys::trace(LOG_ERR, T_ERROR, '[DELETE] '.$id.' FAILURE', null, $this->_);
			return false;
		}
	}

	function clean($mode=self::CLEAN_ALL, $tags=null) {
		sys::trace(LOG_DEBUG, T_CACHE, '[CLEAN]', null, $this->_);
		$this->cache = [];
		switch($mode) {
			case self::CLEAN_ALL:
				$this->Memcached->flush();
				break;
			case self::CLEAN_OLD:
				break;
			case self::CLEAN_ALL_TAG:
			case self::CLEAN_ANY_TAG:
			case self::CLEAN_NOT_TAG:
				//@TODO
				break;
		}
		return true;
	}

	/**
	 * Commit write buffer to SqLite on shutdown
	 */
	static function shutdown() {
		try {
			foreach (self::$buffer as $id => $buffer) {
				sys::trace(LOG_DEBUG, T_CACHE, '[STORE] BUFFER: '.count($buffer).' items on '.$id, null, __METHOD__);
				$Memcached = new \Memcached();
				if(is_array($buffer['params'][0])) $Memcached->addServers($buffer['params']);
				else $Memcached->addServer($buffer['params'][0], $buffer['params'][1], $buffer['params'][2]);
				foreach ($buffer['values'] as $k => $data) {
					list($value, $expire, $tags) = $data;
					if (is_array($tags)) $tags = implode('|', $tags);
					$Memcached->set($k, ['v'=>$value, 'tags'=>$tags], $expire);
				}
				unset($Memcached);
			}
		} finally {
			self::$buffer = [];
		}
	}
}
register_shutdown_function(__NAMESPACE__.'\MemcachedCache::shutdown');
