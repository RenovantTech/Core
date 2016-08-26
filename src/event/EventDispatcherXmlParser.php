<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\event;
use function metadigit\core\trace;
/**
 * EventDispatcherXmlParser
 * @internal
 * @package metadigit\core\event
 */
class EventDispatcherXmlParser {
	use \metadigit\core\CoreTrait;

	/** EventDispatcher namespace
	 * @var string */
	protected $namespace;
	/** EventDispatcher XML path
	 * @var string */
	protected $xmlPath;
	/** EventDispatcher XML
	 * @var \SimpleXMLElement */
	protected $XML;

	/**
	 * @param string $xmlPath	XML path
	 * @param string $namespace	EventDispatcher namespaces
	 */
	function __construct($xmlPath, $namespace) {
		$this->_oid = $namespace.'.EventDispatcherXmlParser';
		$this->namespace = $namespace;
		$this->xmlPath = $xmlPath;
		$this->XML = simplexml_load_file($this->xmlPath);
	}

	/**
	 * Parse XML events & listeners, invokig EventDispatcher->listens()
	 * @param EventDispatcher $EventDispatcher
	 */
	function parseListeners(EventDispatcher $EventDispatcher) {
		foreach($this->XML->xpath('/events/event') as $eventXML) {
			$eventName = (string)$eventXML['name'];
			TRACE and trace(LOG_DEBUG, TRACE_DEFAULT, 'parsing listeners for event "'.$eventName.'"');
			foreach($eventXML->xpath('listeners/listener') as $listenerXML) {
				$priority = (isset($listenerXML['priority'])) ? (int)$listenerXML['priority'] : 1;
				$callback = (string)$listenerXML;
				if(strpos($callback,'->')>0) $callback =  explode('->', $callback);
				$EventDispatcher->listen($eventName, $callback, $priority);
			}
		}
	}
}
