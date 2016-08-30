<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\event;
use function metadigit\core\trace;
use metadigit\core\util\xml\XMLValidator;
/**
 * The EventDispatcher is the core of the framework event system.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class EventDispatcher implements EventDispatcherInterface {
	use \metadigit\core\CoreTrait;

	/** registered listeners (callbacks)
	 * @var array */
	private $listeners = [];
	/** Context namespace
	 * @var string */
	protected $namespace;
	/** XML Parser
	 * @var EventDispatcherXmlParser */
	protected $XmlParser;
	/** EventDispatcher XML path
	 * @var string */
	protected $xmlPath;

	/**
	 * Constructor
	 * @param string		$namespace	EventDispatcher namespace
	 * @param string|null	$xmlPath	optional XML path
	 * @throws EventDispatcherException
	 */
	function __construct($namespace, $xmlPath=null) {
		$this->_oid = $namespace.'.EventDispatcher';
		$this->namespace = $namespace;
		$this->xmlPath = $xmlPath;
		if(!is_null($xmlPath)) {
			if(!file_exists($xmlPath)) throw new EventDispatcherException(11, [$this->_oid, $xmlPath]);
			if(!XMLValidator::schema($xmlPath, __DIR__.'/EventDispatcher.xsd')) throw new EventDispatcherException(12, [$xmlPath]);
			TRACE and trace(LOG_DEBUG, 1, '[START] parsing EventDispatcher XML');
			$this->getXmlParser()->parseListeners($this);
			TRACE and trace(LOG_DEBUG, 1, '[END] EventDispatcher ready');
		}
	}

	function __sleep() {
		return ['_oid', 'listeners', 'namespace', 'xmlPath'];
	}

	/**
	 * @see EventDispatcherInterface
	 */
	function listen($eventName, $callback, $priority=1) {
		$this->listeners[$eventName][(int)$priority][] = $callback;
		krsort($this->listeners[$eventName], SORT_NUMERIC);
	}

	/**
	 * @see EventDispatcherInterface
	 */
	function trigger($eventName, $target=null, array $params=null, $Event=null) {
		if(is_null($Event)) $Event = new Event($target, $params);
		$Event->setName($eventName);
		if(!isset($this->listeners[$eventName])) return $Event;
		foreach($this->listeners[$eventName] as $listeners) {
			foreach($listeners as $callback) {
				call_user_func($callback, $Event);
				if($Event->isPropagationStopped()) break;
			}
		}
		return $Event;
	}

	/**
	 * @return EventDispatcherXmlParser
	 */
	protected function getXmlParser() {
		return (!is_null($this->XmlParser)) ? $this->XmlParser : $this->XmlParser = new EventDispatcherXmlParser($this->xmlPath, $this->namespace);
	}
}
