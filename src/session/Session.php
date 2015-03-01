<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\session;
/**
 * HTTP Session.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Session {

	const DEFAULT_NAMESPACE = 'default';

	const FORCE_NAMESPACE = false;

	const FORCE_SINGLETON = false;

	/** Since expiring data is handled at startup to avoid __destruct difficulties,
	 * the data that will be expiring at end of this request is held here
	 * @var array */
	static protected $_expiringData = [];
	/** Trace current instances to prevent creation of additional accessor instance objects for this namespace
	 * @var array */
	static private $_singletons = [];
	/** Session locking status
	 * @var bool */
	protected $_isLocked = false;
	/** Session namespace
	 * @var string */
	protected $_namespace = self::DEFAULT_NAMESPACE;

	/**
	 * Returns an instance object bound to a particular, isolated section
	 * of the session, identified by $namespace name (defaulting to 'default').
	 * The optional argument $isSingleton will prevent construction of additional
	 * instance objects acting as accessor to this $namespace.
	 * @param string $namespace programmatic name of the requested namespace
	 * @param bool $isSingleton prevent creation of additional accessor instance objects for this namespace
	 * @throws SessionException
	 */
	final function __construct($namespace=null, $isSingleton=false) {
		if(session_status() != 2) throw new SessionException(51);
		if(static::FORCE_NAMESPACE) $this->_namespace = static::FORCE_NAMESPACE;
		else $this->_namespace = (!is_null($namespace)) ? $namespace : static::DEFAULT_NAMESPACE;
		if(!preg_match('/^[a-zA-Z]{1}[_a-zA-Z0-9]*[a-zA-Z0-9]{1}$/', $this->_namespace)) throw new SessionException(52, [$this->_namespace]);
		if(isset(self::$_singletons[$this->_namespace])) throw new SessionException(53, [$this->_namespace]);
		if(static::FORCE_SINGLETON || $isSingleton === true) self::$_singletons[$this->_namespace] = true;
		if (isset($_SESSION['_METADATA_'])) $this->expireData();
	}

	final function __get($k){
		if(isset($_SESSION[$this->_namespace][$k])) return $_SESSION[$this->_namespace][$k];
		elseif(isset(self::$_expiringData[$this->_namespace][$k])) return self::$_expiringData[$this->_namespace][$k];
		else return null;
	}

	final function __isset($k){
		if(isset($_SESSION[$this->_namespace][$k])) return true;
		elseif(isset(self::$_expiringData[$this->_namespace][$k])) return true;
		else return false;
	}

	final function __set($k, $v){
		if($k==='') throw new SessionException("The '$k' key must be a non-empty string");
		if($this->_isLocked) throw new SessionException(61, [$this->_namespace]);
		if(method_exists($this, $method ='set'.ucfirst($k))) $this->$method($v);
		else $_SESSION[$this->_namespace][(string)$k] = $v;
	}

	final function lock() {
		$this->_isLocked = true;
	}

	final function isLocked() {
		return $this->_isLocked;
	}

	/**
	 * Expire the namespace, or specific variables, after a specified number of seconds
	 * @param int $seconds     - expires in this many seconds
	 * @param mixed $variables - OPTIONAL list of variables to expire (defaults to all)
	 * @throws SessionException
	 */
	final function setExpirationSeconds($seconds, $variables=null) {
		if($seconds <= 0) throw new SessionException('Seconds must be positive.');
		if ($variables === null) {
			// apply expiration to entire namespace
			$_SESSION['_METADATA_'][$this->_namespace]['ENT'] = time() + $seconds;
		} else {
			if(is_string($variables)) $variables = [$variables];
			foreach ($variables as $variable) {
				if (!empty($variable)) {
					$_SESSION['_METADATA_'][$this->_namespace]['EVT'][$variable] = time() + $seconds;
				}
			}
		}
	}

	/**
	 * Expire the namespace, or specific variables after a specified number of page hops
	 * @param int $hops        - how many "hops" (number of subsequent requests) before expiring
	 * @param mixed $variables - OPTIONAL list of variables to expire (defaults to all)
	 * @param boolean $hopCountOnUsageOnly - OPTIONAL if set, only count a hop/request if this namespace is used
	 * @throws SessionException
	 */
	final function setExpirationHops($hops, $variables = null, $hopCountOnUsageOnly = false) {
		if($hops <= 0) throw new SessionException('Hops must be positive number.');
		if ($variables === null) {
			// apply expiration to entire namespace
			if ($hopCountOnUsageOnly === false) $_SESSION['_METADATA_'][$this->_namespace]['ENGH'] = $hops;
			else $_SESSION['_METADATA_'][$this->_namespace]['ENNH'] = $hops;
		} else {
			if (is_string($variables)) $variables = [$variables];
			foreach ($variables as $variable) {
				if (!empty($variable)) {
					if ($hopCountOnUsageOnly === false) $_SESSION['_METADATA_'][$this->_namespace]['EVGH'][$variable] = $hops;
					else $_SESSION['_METADATA_'][$this->_namespace]['EVNH'][$variable] = $hops;
				}
			}
		}
	}

	final function unlock() {
		$this->_isLocked = false;
	}

	/**
	 * Namespace data expiration calculations.
	 */
	final private function expireData() {
		$namespace = $this->namespace;
		if (isset($_SESSION['_METADATA_'][$namespace])) {
			// Expire Namespace by Namespace Hop (ENNH)
			if (isset($_SESSION['_METADATA_'][$namespace]['ENNH'])) {
				$_SESSION['_METADATA_'][$namespace]['ENNH']--;
				if ($_SESSION['_METADATA_'][$namespace]['ENNH'] === 0) {
					if (isset($_SESSION[$namespace])) {
						self::$_expiringData[$namespace] = $_SESSION[$namespace];
						unset($_SESSION[$namespace]);
					}
					unset($_SESSION['_METADATA_'][$namespace]);
				}
			}
			// Expire Namespace Variables by Namespace Hop (EVNH)
			if (isset($_SESSION['_METADATA_'][$namespace]['EVNH'])) {
				foreach ($_SESSION['_METADATA_'][$namespace]['EVNH'] as $variable => $hops) {
					$_SESSION['_METADATA_'][$namespace]['EVNH'][$variable]--;
					if ($_SESSION['_METADATA_'][$namespace]['EVNH'][$variable] === 0) {
						if (isset($_SESSION[$namespace][$variable])) {
							self::$_expiringData[$namespace][$variable] = $_SESSION[$namespace][$variable];
							unset($_SESSION[$namespace][$variable]);
						}
						unset($_SESSION['_METADATA_'][$namespace]['EVNH'][$variable]);
					}
				}
				if(empty($_SESSION['_METADATA_'][$namespace]['EVNH'])) unset($_SESSION['_METADATA_'][$namespace]['EVNH']);
			}
		}
		if (empty($_SESSION['_METADATA_'][$namespace])) unset($_SESSION['_METADATA_'][$namespace]);
		if (empty($_SESSION['_METADATA_'])) unset($_SESSION['_METADATA_']);
	}

	/**
	 * Global data expiration calculations.
	 */
	final static function expireGlobalData() {
		if (isset($_SESSION['_METADATA_'])) {
			foreach ($_SESSION['_METADATA_'] as $namespace => $metadata) {
				// Expire Namespace by Time (ENT)
				if (isset($metadata['ENT']) && ($metadata['ENT'] > 0) && (time() > $metadata['ENT']) ) {
					unset($_SESSION[$namespace]);
					unset($_SESSION['_METADATA_'][$namespace]);
				}
				// Expire Namespace by Global Hop (ENGH) if it wasnt expired above
				if (isset($_SESSION['_METADATA_'][$namespace]) && isset($metadata['ENGH']) && $metadata['ENGH'] >= 1) {
					$_SESSION['_METADATA_'][$namespace]['ENGH']--;
					if ($_SESSION['_METADATA_'][$namespace]['ENGH'] === 0) {
						if (isset($_SESSION[$namespace])) {
							self::$_expiringData[$namespace] = $_SESSION[$namespace];
							unset($_SESSION[$namespace]);
						}
						unset($_SESSION['_METADATA_'][$namespace]);
					}
				}
				// Expire Namespace Variables by Time (EVT)
				if (isset($metadata['EVT'])) {
					foreach ($metadata['EVT'] as $variable => $time) {
						if (time() > $time) {
							unset($_SESSION[$namespace][$variable]);
							unset($_SESSION['_METADATA_'][$namespace]['EVT'][$variable]);
						}
					}
					if(empty($_SESSION['_METADATA_'][$namespace]['EVT'])) unset($_SESSION['_METADATA_'][$namespace]['EVT']);
				}
				// Expire Namespace Variables by Global Hop (EVGH)
				if (isset($metadata['EVGH'])) {
					foreach ($metadata['EVGH'] as $variable => $hops) {
						$_SESSION['_METADATA_'][$namespace]['EVGH'][$variable]--;

						if ($_SESSION['_METADATA_'][$namespace]['EVGH'][$variable] === 0) {
							if (isset($_SESSION[$namespace][$variable])) {
								self::$_expiringData[$namespace][$variable] = $_SESSION[$namespace][$variable];
								unset($_SESSION[$namespace][$variable]);
							}
							unset($_SESSION['_METADATA_'][$namespace]['EVGH'][$variable]);
						}
					}
					if (empty($_SESSION['_METADATA_'][$namespace]['EVGH'])) unset($_SESSION['_METADATA_'][$namespace]['EVGH']);
				}
			}
			if (isset($namespace) && empty($_SESSION['_METADATA_'][$namespace])) unset($_SESSION['_METADATA_'][$namespace]);
		}
		if (isset($_SESSION['_METADATA_']) && empty($_SESSION['_METADATA_'])) unset($_SESSION['_METADATA_']);
	}
}
Session::expireGlobalData();
