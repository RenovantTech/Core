<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\context;
use const metadigit\core\trace\{T_DEPINJ};
use metadigit\core\sys,
	metadigit\core\CoreProxy,
	metadigit\core\container\Container,
	metadigit\core\container\ContainerException;
/**
 * Context
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Context {
	use \metadigit\core\CoreTrait;
	const ACL_SKIP = true;

	const FAILURE_EXCEPTION	= 1;
	const FAILURE_SILENT	= 2;

	/** instantiated contexts
	 * @var array */
	static protected $_instances = [];

	/**
	 * Factory method to build a Context
	 * @param string $namespace Context namespace
	 * @param boolean $useCache default TRUE, set FALSE to rebuild Context from XML skipping system cache
	 * @return Context
	 */
	static function factory($namespace, $useCache=true) {
		if($useCache && isset(self::$_instances[$namespace]))
			return self::$_instances[$namespace];
		elseif($useCache && $Context = sys::cache('sys')->get($namespace.'.Context'))
			return self::$_instances[$namespace] = $Context;
		else {
			sys::trace(LOG_DEBUG, T_DEPINJ, $namespace, null, __METHOD__);
			self::$_instances[$namespace] = $Context = new Context($namespace);
			sys::cache('sys')->set($namespace.'.Context', $Context);
			return $Context;
		}
	}

	/** Map of available objects & their classes
	 * @var array */
	protected $id2classMap = [];
	/** Included Contexts namespaces
	 * @var array */
	protected $includedNamespaces = [];
	/** registered listeners (callbacks)
	 * @var array */
	protected $listeners = [];
	/** Context namespace
	 * @var string */
	protected $namespace;
	/** Array of instantiated objects (to avoid replication)
	 * @var array */
	protected $objects = [];

	/**
	 * Constructor
	 * @param string $namespace Context namespace
	 * @throws ContextException
	 */
	function __construct($namespace) {
		$this->_oid = $namespace.'.Context';
		$this->namespace = $namespace;
		// parse YAML
		ContextYamlParser::parse($this->namespace, $this->includedNamespaces, $this->id2classMap, $this->listeners);
		// create Container
		$Container = new Container($namespace, $this->includedNamespaces);
		sys::cache('sys')->set($namespace.'.Container', $Container);
		$this->__wakeup();
	}

	function __sleep() {
		return ['_oid', 'id2classMap', 'includedNamespaces', 'listeners', 'namespace'];
	}

	function __wakeup() {
		foreach ($this->listeners as $eventName => $eventListeners) {
			foreach ($eventListeners as $priority => $listeners) {
				foreach ($listeners as $callback) {
					sys::listen($eventName, $callback, $priority);
				}
			}
		}
	}

	/**
	 * Return TRUE if contains object (optionally verifying class)
	 * @param string $id object OID
	 * @param string $class class/interface that object must extend/implement (optional)
	 * @return boolean
	 */
	function has($id, $class=null) {
		return ( isset($this->id2classMap[$id]) && ( is_null($class) || (in_array($class,$this->id2classMap[$id])) ) ) ? true : false;
	}

	/**
	 * Get an object Proxy
	 * @param string $id object identifier
	 * @param string $class required object class
	 * @param integer $failureMode failure mode when the object does not exist
	 * @return object
	 * @throws ContextException
	 */
	function get($id, $class=null, $failureMode=self::FAILURE_EXCEPTION) {
		sys::trace(LOG_DEBUG, T_DEPINJ, 'GET '.$id, null, $this->_oid);
		if(isset($this->objects[$id]) && (is_null($class) || $this->objects[$id] instanceof $class)) return $this->objects[$id];
		try {
			$Obj = null;
			if($this->has($id, $class)) {
				$Obj = new CoreProxy($id);
			} else {
				$ctxNamespace = null;
				foreach($this->includedNamespaces as $namespace) {
					if(strpos($id, $namespace)===0) $ctxNamespace = $namespace;
				}
				if(!is_null($ctxNamespace))
					$Obj = self::factory($ctxNamespace)->get($id, $class);
			}
			if(is_null($Obj)) throw new ContextException(1, [$this->_oid, $id]);
			$this->objects[$id] = $Obj;
			return $Obj;
		} catch(ContainerException $Ex) {
			if($failureMode==self::FAILURE_SILENT) return null;
			throw new ContextException($Ex->getCode(), $Ex->getMessage());
		}
	}

	/**
	 * return Dependency Injector Container
	 * @return \metadigit\core\container\Container
	 */
	function getContainer() {
		return sys::cache('sys')->get($this->namespace.'.Container');
	}
}
