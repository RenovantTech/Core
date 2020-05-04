<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\context;
use const renovant\core\SYS_CACHE;
use const renovant\core\trace\{T_DEPINJ};
use renovant\core\sys,
	renovant\core\CoreProxy,
	renovant\core\container\Container,
	renovant\core\container\ContainerException,
	renovant\core\container\ContainerYamlParser,
	renovant\core\event\EventDispatcher,
	renovant\core\event\EventDispatcherException,
	renovant\core\event\EventYamlParser;
/**
 * Context
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
class Context {
	use \renovant\core\CoreTrait;
	const ACL_SKIP = true;

	const FAILURE_EXCEPTION	= 1;
	const FAILURE_SILENT	= 2;

	/** Container instance
	 * @var Container */
	protected $Container;
	/** EventDispatcher instance
	 * @var EventDispatcher */
	protected $EventDispatcher;
	/** initialized namespaces
	 * @var array */
	protected $namespaces = [];
	/** Array of instantiated services (to avoid replication)
	 * @var array */
	protected $services = [];

	/**
	 * Constructor
	 * @param Container $Container
	 * @param EventDispatcher $EventDispatcher
	 */
	function __construct(Container $Container, EventDispatcher $EventDispatcher) {
		$this->Container = $Container;
		$this->EventDispatcher = $EventDispatcher;
	}

	/**
	 * @return Container
	 */
	function container(): Container {
		return $this->Container;
	}

	/**
	 * Initialize namespace
	 * @param string $namespace Context namespace
	 * @throws ContainerException
	 * @throws ContextException
	 * @throws EventDispatcherException
	 */
	function init($namespace) {
		if(in_array($namespace, $this->namespaces)) return;
		sys::trace(LOG_DEBUG, T_DEPINJ, $namespace, null, 'sys.Context->init');
		$this->namespaces[] = $namespace;
		if(!$context = sys::cache(SYS_CACHE)->get($namespace.'.$context')) {
			$context['includes'] = ContextYamlParser::parseNamespace($namespace);
			$context['container'] = ContainerYamlParser::parseNamespace($namespace);
			$context['events'] = EventYamlParser::parseNamespace($namespace);
			$services = $context['container']['services'];
			unset($context['container']['services']);
			sys::cache(SYS_CACHE)->set($namespace.'.$context', $context);
			sys::cache(SYS_CACHE)->set($namespace.'.$services', $services);
		}
		$this->Container->init($namespace, $context['container']);
		$this->EventDispatcher->init($namespace, $context['events']);
		foreach ($context['includes'] as $ns) $this->init($ns);
	}

	/**
	 * Return TRUE if contains object (optionally verifying class)
	 * @param string $id object OID
	 * @param string $class class/interface that object must extend/implement (optional)
	 * @return boolean
	 */
	function has($id, $class=null): bool {
		return $this->Container->has($id, $class);
	}

	/**
	 * Get an object Proxy
	 * @param string $id           object identifier
	 * @param string $class        required object class
	 * @param integer $failureMode failure mode when the object does not exist
	 * @return object
	 * @throws ContextException
	 * @throws EventDispatcherException|\ReflectionException
	 */
	function get($id, $class=null, $failureMode=self::FAILURE_EXCEPTION) {
		sys::trace(LOG_DEBUG, T_DEPINJ, $id, null, 'sys.Context->get');
		if(isset($this->services[$id]) && (is_null($class) || $this->services[$id] instanceof $class)) return $this->services[$id];
		try {
			$this->init(substr($id, 0, strrpos($id, '.')));
			if($this->has($id, $class)) {
				if(substr($id, 0, 4)=='sys.') {
					if(!$Obj = sys::cache(SYS_CACHE)->get($id))
						$Obj = $this->Container->get($id, $class, $failureMode);
					return $this->services[$id] = $Obj;
				}
				return $this->services[$id] = new CoreProxy($id);
			} elseif($failureMode==self::FAILURE_SILENT) return null;
			else throw new ContextException(1, [$this->_, $id]);
		} catch(ContainerException $Ex) {
			if($failureMode==self::FAILURE_SILENT) return null;
			throw new ContextException($Ex->getCode(), $Ex->getMessage());
		}
	}
}
