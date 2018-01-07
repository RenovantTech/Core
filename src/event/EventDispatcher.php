<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\event;
use const metadigit\core\trace\T_EVENT;
use metadigit\core\sys,
	metadigit\core\CoreProxy;
/**
 * The EventDispatcher is the core of the framework event system.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class EventDispatcher {

	/** registered listeners (callbacks)
	 * @var array */
	protected $listeners = [];
	/** initialized namespaces
	 * @var array */
	protected $namespaces = [];

	/**
	 * Initialize namespace
	 * @param string $namespace Container namespace
	 * @param array|null $eventsMaps
	 * @throws EventDispatcherException
	 */
	function init($namespace, array $eventsMaps=null) {
		if(in_array($namespace, $this->namespaces)) return;
		//sys::trace(LOG_DEBUG, T_EVENT, $namespace, null, 'sys.EventDispatcher->init');
		$this->namespaces[] = $namespace;
		$listeners = $eventsMaps ?? EventYamlParser::parseNamespace($namespace);
		$this->listeners = array_merge($this->listeners, $listeners);
		krsort($this->listeners, SORT_NUMERIC);

	}

	/**
	 * Add an Event listener on the specified event
	 * @param string $eventName the name of the event to listen for
	 * @param callable $callback the callback function to be invoked
	 * @param int $priority trigger precedence on the listeners chain (higher values execute earliest)
	 */
	function listen($eventName, $callback, $priority=1) {
		$this->listeners[$eventName][(int)$priority][] = $callback;
		krsort($this->listeners[$eventName], SORT_NUMERIC);
	}

	/**
	 * Trigger an Event, calling attached listeners
	 * @param string $eventName	the name of the event
	 * @param Event|array|null $EventOrParams custom Event object or params array
	 * @return Event the Event object
	 */
	function trigger($eventName, $EventOrParams=null): Event {
		if(!isset($this->listeners[$eventName]))
			sys::trace(LOG_DEBUG, T_EVENT, strtoupper($eventName));
		$Event = (is_object($EventOrParams)) ? $EventOrParams : new Event($EventOrParams);
		if(!isset($this->listeners[$eventName])) return $Event;
		foreach($this->listeners[$eventName] as $priority => $listeners) {
			foreach($listeners as $callback) {
				if(is_string($callback)) {
					sys::trace(LOG_DEBUG, T_EVENT, strtoupper($eventName).' ['.$priority.'] '.$callback);
					if(strpos($callback,'->')>0) {
						list($objID, $method) = explode('->', $callback);
						$callback = [new CoreProxy($objID), $method];
					}
				} else {
					list($Obj, $method) = $callback;
					$RefProp = new \ReflectionProperty($Obj, '_');
					$RefProp->setAccessible(true);
					$_ = $RefProp->getValue($Obj);
					sys::trace(LOG_DEBUG, T_EVENT, strtoupper($eventName).' ['.$priority.'] '.$_.'->'.$method);
				}
				call_user_func($callback, $Event);
				if($Event->isPropagationStopped()) break;
			}
		}
		return $Event;
	}
}
