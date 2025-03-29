<?php
namespace renovant\core\context;

use renovant\core\{CoreProxy, sys};
use renovant\core\event\{EventDispatcher, EventDispatcherException, EventYamlParser};
use renovant\core\container\{Container, ContainerException, ContainerYamlParser};

use const renovant\core\SYS_CACHE;
use const renovant\core\trace\{T_DEPINJ};

class Context {
	use \renovant\core\CoreTrait;

	public const int FAILURE_EXCEPTION = 1;
	public const int FAILURE_SILENT    = 2;

	/** Container instance
	 * @var Container */
	protected $Container;
	/** EventDispatcher instance
	 * @var EventDispatcher */
	protected $EventDispatcher;
	/** initialized contexts */
	protected array $contexts = [];
	/** Array of instantiated services (to avoid replication) */
	protected array $services = [];

	/**
	 * Constructor
	 * @param Container $Container
	 * @param EventDispatcher $EventDispatcher
	 */
	public function __construct(Container $Container, EventDispatcher $EventDispatcher) {
		$this->Container       = $Container;
		$this->EventDispatcher = $EventDispatcher;
	}

	/**
	 * @return Container
	 */
	public function container(): Container {
		return $this->Container;
	}

	/**
	 * Initialize context
	 * @param string $context Context space
	 * @throws ContainerException
	 * @throws ContextException
	 * @throws EventDispatcherException
	 */
	public function init(string $context) {
		if (in_array($context, $this->contexts)) {
			return;
		}
		sys::trace(LOG_DEBUG, T_DEPINJ, $context, null, 'sys.Context->init');
		$this->contexts[] = $context;
		if (!$data = sys::cache(SYS_CACHE)->get($context . '.$context')) {
			$data              = [];
			$data['includes']  = ContextYamlParser::parse($context);
			$data['container'] = ContainerYamlParser::parse($context);
			$data['events']    = EventYamlParser::parse($context);
			$services          = $data['container']['services'];
			unset($data['container']['services']);
			sys::cache(SYS_CACHE)->set($context . '.$context', $data);
			sys::cache(SYS_CACHE)->set($context . '.$services', $services);
		}
		$this->Container->init($context, $data['container']);
		$this->EventDispatcher->init($context, $data['events']);
		foreach ($data['includes'] as $ns) {
			$this->init($ns);
		}
	}

	/**
	 * Return TRUE if contains object (optionally verifying class)
	 * @param string $id object OID
	 * @param string|null $class class/interface that object must extend/implement (optional)
	 */
	public function has(string $id, ?string $class = null): bool {
		return $this->Container->has($id, $class);
	}

	/**
	 * Get an object Proxy
	 * @param string $id           object identifier
	 * @param string|null $class        required object class
	 * @param integer $failureMode failure mode when the object does not exist
	 * @return object|null
	 * @throws ContextException
	 * @throws EventDispatcherException|\ReflectionException
	 */
	public function get(string $id, ?string $class = null, int $failureMode = self::FAILURE_EXCEPTION) {
		sys::trace(LOG_DEBUG, T_DEPINJ, $id, null, 'sys.Context->get');
		if (isset($this->services[$id]) && (is_null($class) || $this->services[$id] instanceof $class)) {
			return $this->services[$id];
		}
		try {
			$this->init(substr($id, 0, strrpos($id, '.')));
			if ($this->has($id, $class)) {
				if (substr($id, 0, 4) == 'sys.') {
					if (!$Obj = sys::cache(SYS_CACHE)->get($id)) {
						$Obj = $this->Container->get($id, $class, $failureMode);
					}
					return $this->services[$id] = $Obj;
				}
				return $this->services[$id] = new CoreProxy($id);
			} elseif ($failureMode == self::FAILURE_SILENT) {
				return null;
			} else {
				throw new ContextException(1, [$this->_, $id]);
			}
		} catch (ContainerException $Ex) {
			if ($failureMode == self::FAILURE_SILENT) {
				return null;
			}
			throw new ContextException($Ex->getCode(), $Ex->getMessage());
		}
	}
}
