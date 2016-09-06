<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\context;
use const metadigit\core\{TRACE, TRACE_DEPINJ, TRACE_EVENT};
use function metadigit\core\{cache, trace};
use metadigit\core\CoreProxy,
	metadigit\core\Kernel,
	metadigit\core\container\Container,
	metadigit\core\container\ContainerException,
	metadigit\core\event\Event,
	metadigit\core\event\EventDispatcherInterface;
/**
 * Context
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Context implements EventDispatcherInterface {
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
		elseif($useCache && $Context = cache('kernel')->get($namespace.'.Context'))
			return self::$_instances[$namespace] = $Context;
		else {
			TRACE and trace(LOG_DEBUG, TRACE_DEPINJ, $namespace, null, __METHOD__);
			list($namespace2, $className, $dirName, $fileName) = Kernel::parseClassName(str_replace('.','\\', $namespace.'.Context'));
			if(empty($dirName))
				$xmlPath = \metadigit\core\BASE_DIR.$namespace.'-context.xml';
			else
				$xmlPath = $dirName.DIRECTORY_SEPARATOR.'context.xml';
			self::$_instances[$namespace] = $Context = new Context($namespace, $xmlPath);
			cache('kernel')->set($namespace.'.Context', $Context);
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
		cache('kernel')->set($namespace.'.Container', $Container);
	}

	function __sleep() {
		return ['_oid', 'id2classMap', 'includedNamespaces', 'listeners', 'namespace'];
	}

	/**
	 * @see \metadigit\core\event\EventDispatcherInterface
	 */
	function listen($eventName, $callback, $priority=1) {
		$this->listeners[$eventName][(int)$priority][] = $callback;
		krsort($this->listeners[$eventName], SORT_NUMERIC);
	}

	/**
	 *  Return TRUE if contains object (optionally verifiyng class)
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
		TRACE and trace(LOG_DEBUG, TRACE_DEPINJ, 'GET '.$id, null, $this->_oid);
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
		return cache('kernel')->get($this->namespace.'.Container');
	}

	/**
	 * @see \metadigit\core\event\EventDispatcherInterface
	 */
	function trigger($eventName, $target=null, array $params=null, $Event=null) {
		TRACE and trace(LOG_DEBUG, TRACE_EVENT, strtoupper($eventName));
		$params['Context'] = $this;
		if(is_null($Event)) $Event = new Event($target, $params);
		$Event->setName($eventName);
		if(!isset($this->listeners[$eventName])) return $Event;
		foreach($this->listeners[$eventName] as $listeners) {
			foreach($listeners as $callback) {
				if(is_string($callback) && strpos($callback,'->')>0) {
					$callback = explode('->', $callback);
					$callback[0] = $this->get($callback[0]);
				}
				call_user_func($callback, $Event);
				if($Event->isPropagationStopped()) break;
			}
		}
		return $Event;
	}
}
