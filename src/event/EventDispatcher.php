<?php
namespace renovant\core\event;
use const renovant\core\trace\T_EVENT;
use renovant\core\sys,
	renovant\core\context\ContextException;
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
	function init(string $namespace, array $eventsMaps=null) {
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
	function listen(string $eventName, callable $callback, $priority=1) {
		$eventName = strtoupper($eventName);
		$this->listeners[$eventName][(int)$priority][] = $callback;
		krsort($this->listeners[$eventName], SORT_NUMERIC);
	}

	/**
	 * Trigger an Event, calling attached listeners
	 * @param string $eventName the name of the event
	 * @param Event|array|null $EventOrParams custom Event object or params array
	 * @return Event the Event object
	 * @throws ContextException
	 * @throws EventDispatcherException
	 * @throws \ReflectionException
	 */
	function trigger(string $eventName, $EventOrParams=null): Event {
		$eventName = strtoupper($eventName);
		if(!isset($this->listeners[$eventName]))
			sys::trace(LOG_DEBUG, T_EVENT, $eventName);
		$Event = (is_object($EventOrParams)) ? $EventOrParams : new Event($EventOrParams);
		if(!isset($this->listeners[$eventName])) return $Event;
		$Context = sys::context();
		foreach($this->listeners[$eventName] as $priority => $listeners) {
			foreach($listeners as $callback) {
				if(is_string($callback)) {
					sys::trace(LOG_DEBUG, T_EVENT, $eventName.' ['.$priority.'] '.$callback);
					if(strpos($callback,'->')>0) {
						list($objID, $method) = explode('->', $callback);
						$callback = [$Context->get($objID), $method];
					}
				} elseif(is_array($callback)) {
					list($Obj, $method) = $callback;
					$RefProp = new \ReflectionProperty($Obj, '_');
					$RefProp->setAccessible(true);
					$_ = $RefProp->getValue($Obj);
					sys::trace(LOG_DEBUG, T_EVENT, $eventName.' ['.$priority.'] '.$_.'->'.$method);
				} elseif(is_callable($callback)) {
					sys::trace(LOG_DEBUG, T_EVENT, $eventName.' ['.$priority.'] callable function');
				}
				call_user_func($callback, $Event);
				if($Event->isPropagationStopped()) break;
			}
		}
		return $Event;
	}
}
