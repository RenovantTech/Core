<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\event;
/**
 * EventDispatcher interface
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
interface EventDispatcherInterface {

	/**
	 * Add an Event listener on the specified event
	 * @param string   $eventName the name of the event to listen for
	 * @param callable $callback  the callback function to be invoked
	 * @param int      $priority  trigger precedence on the listeners chain (higher values execute earliest)
	 * @throws \Exception
	 */
	function listen($eventName, $callback, $priority=1);

	/**
	 * Trigger an Event, calling attached listeners
	 * @param string		$eventName	the name of the event
	 * @param mixed			$target		Event's target
	 * @param array			$params		Event's parameters
	 * @param Event|null	$Event		optional custom Event object
	 * @return Event the Event object
	 */
	function trigger($eventName, $target=null, array $params=null, $Event=null);
}