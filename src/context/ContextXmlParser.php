<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\context;
/**
 * ContextParser
 * @internal
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class ContextXmlParser {
	use \metadigit\core\CoreTrait;

	/** Included Contexts namespaces
	 * @var array */
	protected $includes = [];
	/** Context namespace
	 * @var string */
	protected $namespace;
	/** Context XML path
	 * @var string */
	protected $xmlPath;
	/** Context XML
	 * @var \SimpleXMLElement */
	protected $XML;

	/**
	 * @param string $namespace ContextParser namespace
	 * @param string $xmlPath XML path
	 */
	function __construct($namespace, $xmlPath) {
		$this->_oid = $namespace.'.ContextXmlParser';
		$this->namespace = $namespace;
		$this->xmlPath = $xmlPath;
		$this->XML = simplexml_load_string(self::parseConstants(file_get_contents($this->xmlPath)));
		foreach($this->XML->xpath('//includes/include') as $objXML){
			$this->includes[] = (string)$objXML['namespace'];
		}
	}

	/**
	 * Verify Context namespaces
	 * @throws ContextException
	 * @return boolean
	 */
	function verify() {
		$namespace = (string)$this->XML['namespace'];
		$availableNamespaces = implode(', ', array_merge((array)$namespace, $this->includes));
		if($this->namespace != $namespace) throw new ContextException(13, [$this->_oid, $namespace]);
		foreach($this->XML->xpath('//objects/object') as $objXML){
			$id = (string)$objXML['id'];
			if(strpos($id, $this->namespace) !== 0) throw new ContextException(14, [$this->_oid, $id, $this->namespace]);
		}
		foreach($this->XML->xpath('//objects/object/constructor/arg[@type="object"]') as $objXML){
			$id = (string)$objXML;
			if(strpos($id, $this->namespace.'.') === 0) continue;
			foreach($this->includes as $ns) {
				if(strpos($id, $ns.'.') === 0) continue 2;
			}
			throw new ContextException(15, [$this->_oid, (string)$objXML['name'], $id, $availableNamespaces]);
		}
		foreach($this->XML->xpath('//objects/object/properties/property[@type="object"]') as $objXML){
			$id = (string)$objXML;
			if(strpos($id, $this->namespace.'.') === 0) continue;
			foreach($this->includes as $ns) {
				if(strpos($id, $ns.'.') === 0) continue 2;
			}
			throw new ContextException(16, [$this->_oid, (string)$objXML['name'], $id, $availableNamespaces]);
		}
		return true;
	}

	/**
	 * Parse XML events & listeners, invokig Context->listens()
	 * @param Context $Context
	 */
	function parseEventListeners(Context $Context) {
		// parse events in XML
		if(isset($this->XML->xpath('//events')[0])) {
			foreach($this->XML->xpath('//events/event') as $eventXML) {
				$eventName = (string)$eventXML['name'];
				TRACE and $this->trace(LOG_DEBUG, 1, __FUNCTION__, 'parsing listeners for event "'.$eventName.'"');
				foreach($eventXML->xpath('listeners/listener') as $listenerXML) {
					$priority = (isset($listenerXML['priority'])) ? (int)$listenerXML['priority'] : 1;
					$callback = (string)$listenerXML;
					$Context->listen($eventName, $callback, $priority);
				}
			}
		}
		// scan for EventListenerInterface objects
		if(isset($this->XML->xpath('//objects')[0])) {
			foreach($this->XML->xpath('//objects/object') as $objectXML) {
				$id = (string)$objectXML['id'];
				$class = (string)$objectXML['class'];
				if((new \ReflectionClass($class))->implementsInterface('metadigit\core\event\EventSubscriberInterface')) {
					$events = $class::getSubscribedEvents();
					foreach($events as $eventName => $callbackArray) {
						foreach($callbackArray as $callbackParams) {
							$method = $callbackParams[0];
							$priority = $callbackParams[1];
							$Context->listen($eventName, $id.'->'.$method, $priority);
						}
					}
				}
			}
		}
	}

	/**
	 * Return included Contexts namespaces
	 * @return array
	 */
	function getIncludes() {
		return $this->includes;
	}

	static function parseConstants($string, $Obj=null) {
		$_consts = [
			'${BASE_DIR}'=> \metadigit\core\BASE_DIR
		];
		if(is_object($Obj))						$_consts['${ID}']			= $Obj->oid();
		if(defined('metadigit\core\APP'))		$_consts['${APP}']			= \metadigit\core\APP;
		if(defined('metadigit\core\PUBLIC_DIR'))$_consts['${PUBLIC_DIR}']	= \metadigit\core\PUBLIC_DIR;
		return strtr($string,$_consts);
	}
}
