<?php
namespace renovant\core\event;

use renovant\core\sys;
use renovant\core\context\ContextException;

use const renovant\core\trace\T_EVENT;

class EventDispatcher {
	/** registered listeners (callbacks) */
	protected array $listeners = [];
	/** initialized contexts */
	protected array $contexts = [];
	/** shutdown events queue */
	protected static array $queue = [];

	/**
	 * Initialize context
	 * @param string $context Container context
	 * @param array|null $eventsMaps
	 * @throws EventDispatcherException
	 */
	public function init(string $context, array $eventsMaps = null) {
		if (in_array($context, $this->contexts)) {
			return;
		}
		//sys::trace(LOG_DEBUG, T_EVENT, $context, null, 'sys.EventDispatcher->init');
		$this->contexts[] = $context;
		$listeners        = $eventsMaps ?? EventYamlParser::parse($context);
		$this->listeners  = array_merge($this->listeners, $listeners);
		krsort($this->listeners, SORT_NUMERIC);
	}

	/**
	 * Add an Event listener on the specified event
	 * @param string $eventName the name of the event to listen for
	 * @param callable $callback the callback function to be invoked
	 * @param int $priority trigger precedence on the listeners chain (higher values execute earliest)
	 */
	public function listen(string $eventName, callable $callback, int $priority = 1) {
		$eventName                                = strtoupper($eventName);
		$this->listeners[$eventName][$priority][] = $callback;
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
	public function trigger(string $eventName, Event|array|null $EventOrParams = null): Event {
		$eventName = strtoupper($eventName);
		if (!isset($this->listeners[$eventName])) {
			sys::trace(LOG_DEBUG, T_EVENT, $eventName);
		}
		$Event = (is_object($EventOrParams)) ? $EventOrParams : new Event($EventOrParams);
		if (!isset($this->listeners[$eventName])) {
			return $Event;
		}
		$Context = sys::context();
		foreach ($this->listeners[$eventName] as $priority => $listeners) {
			foreach ($listeners as $callback) {
				if (is_string($callback)) {
					sys::trace(LOG_DEBUG, T_EVENT, $eventName . ' [' . $priority . '] ' . $callback);
					if (strpos($callback, '->') > 0) {
						list($objID, $method) = explode('->', $callback);
						$callback             = [$Context->get($objID), $method];
					}
				} elseif (is_array($callback)) {
					list($Obj, $method) = $callback;
					$RefProp            = new \ReflectionProperty($Obj, '_');
					$RefProp->setAccessible(true);
					$_ = $RefProp->getValue($Obj);
					sys::trace(LOG_DEBUG, T_EVENT, $eventName . ' [' . $priority . '] ' . $_ . '->' . $method);
				} elseif (is_callable($callback)) {
					sys::trace(LOG_DEBUG, T_EVENT, $eventName . ' [' . $priority . '] callable function');
				}
				call_user_func($callback, $Event);
				if ($Event->isPropagationStopped()) {
					break;
				}
			}
		}
		return $Event;
	}

	/**
	 * Enqueue event to be triggered on shutdown
	 * @param string $eventName
	 * @param Event|array|null $EventOrParams custom Event object or params array
	 * @return void
	 */
	public function enqueue(string $eventName, Event|array|null $EventOrParams = null): void {
		$eventName = strtoupper($eventName);
		sys::trace(LOG_DEBUG, T_EVENT, '[ENQUEUE] ' . $eventName);
		self::$queue[] = [$eventName, $EventOrParams];
	}

	/**
	 * @throws \ReflectionException|EventDispatcherException|ContextException
	 */
	public static function shutdown() {
		if (empty(self::$queue)) {
			return;
		}
		$prevTraceFn = sys::traceFn('sys.EventDispatcher::' . __FUNCTION__);
		try {
			foreach (self::$queue as $i => list($eventName, $EventOrParams)) {
				sys::event()->trigger($eventName, $EventOrParams);
				unset(self::$queue[$i]);
			}
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}
}
if (PHP_SAPI != 'cli') {
	register_shutdown_function(__NAMESPACE__ . '\EventDispatcher::shutdown');
}
