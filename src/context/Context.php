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
	metadigit\core\container\ContainerException,
	metadigit\core\container\ContainerYamlParser,
	metadigit\core\event\EventDispatcher,
	metadigit\core\event\EventDispatcherException,
	metadigit\core\event\EventYamlParser;
/**
 * Context
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Context {
	use \metadigit\core\CoreTrait;
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
	/** Array of instantiated objects (to avoid replication)
	 * @var array */
	protected $objects = [];

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
		if(!$context = sys::cache('sys')->get($namespace.'#context')) {
			$context['includes'] = ContextYamlParser::parseNamespace($namespace);
			$context['container'] = ContainerYamlParser::parseNamespace($namespace);
			$context['events'] = EventYamlParser::parseNamespace($namespace);
			$services = $context['container']['services'];
			unset($context['container']['services']);
			sys::cache('sys')->set($namespace.'#context', $context);
			sys::cache('sys')->set($namespace.'#services', $services);
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
	 * @throws EventDispatcherException
	 */
	function get($id, $class=null, $failureMode=self::FAILURE_EXCEPTION) {
		sys::trace(LOG_DEBUG, T_DEPINJ, $id, null, 'sys.Context->get');
		if(isset($this->objects[$id]) && (is_null($class) || $this->objects[$id] instanceof $class)) return $this->objects[$id];
		try {
			sys::context()->init(substr($id, 0, strrpos($id, '.')));
			if($this->has($id, $class)) {
				return $this->objects[$id] = new CoreProxy($id);
			} elseif($failureMode==self::FAILURE_SILENT) return null;
			else throw new ContextException(1, [$this->_, $id]);
		} catch(ContainerException $Ex) {
			if($failureMode==self::FAILURE_SILENT) return null;
			throw new ContextException($Ex->getCode(), $Ex->getMessage());
		}
	}
}
