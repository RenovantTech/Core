<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\container;
use function metadigit\core\trace;
use metadigit\core\Kernel,
	metadigit\core\util\xml\XMLValidator;
/**
 * Dependency Injection Container
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Container {
	use \metadigit\core\CoreTrait;

	const FAILURE_EXCEPTION	= 1;
	const FAILURE_SILENT	= 2;

	/** Included Container namespaces
	 * @var array */
	protected $includes = [];
	/** Mapping between objects IDs and their parent classes and interfaces.
	 * @var array */
	protected $id2classMap = [];
	/** Mapping between classes and objects IDs.
	 * @var array */
	protected $class2idMap = [];
	/** Container namespace
	 * @var string */
	protected $namespace;
	/** Array of instantiated objects (to avoid replication)
	 * @var array */
	protected $objects = [];
	/** YAML Parser
	 * @var ContainerYamlParser */
	protected $YamlParser;

	/**
	 * Constructor
	 * @param string $namespace Container namespace
	 * @param array  $includes  available namespaces
	 * @throws ContainerException
	 */
	function __construct($namespace, $includes=[]) {
		$this->_oid = $namespace.'.Container';
		$this->namespace = $namespace;
		$this->includes = $includes;
		$this->getYamlParser()->parseMaps($this->id2classMap, $this->class2idMap);
	}

	function __sleep() {
		return ['_oid', 'includes', 'id2classMap', 'class2idMap', 'namespace'];
	}

	/**
	 * Get an object
	 * @param string $id object OID
	 * @param string $class required object class
	 * @param integer $failureMode failure mode when the object does not exist
	 * @return object
	 * @throws ContainerException
	 */
	function get($id, $class=null, $failureMode=self::FAILURE_EXCEPTION) {
		TRACE and trace(LOG_DEBUG, TRACE_DEPINJ, 'GET '.$id, null, $this->_oid);
		if(isset($this->objects[$id]) && (is_null($class) || $this->objects[$id] instanceof $class)) return $this->objects[$id];
		try {
			if(!$this->has($id)) throw new ContainerException(1, [$this->_oid, $id]);
			if(!$this->has($id, $class)) throw new ContainerException(2, [$this->_oid, $id, $class]);
			$objClass = $this->id2classMap[$id][0];
			$args = $this->getYamlParser()->parseObjectConstructorArgs($id);
			$ReflClass = new \ReflectionClass($objClass);
			$Obj = (empty($args)) ? $ReflClass->newInstance() : $ReflClass->newInstanceArgs($args);
			$ReflObject = new \ReflectionObject($Obj);
			$this->setProperty('_oid', $id, $Obj, $ReflObject);
			$properties = $this->getYamlParser()->parseObjectProperties($id);
			foreach ($properties as $k=>$v) {
				$this->setProperty($k, $v, $Obj, $ReflObject);
			}
			$this->objects[$id] = $Obj;
			Kernel::cache('kernel')->set($id, $Obj);
			return $Obj;
		} catch(ContainerException $Ex) {
			if($failureMode==self::FAILURE_SILENT) return null;
			throw $Ex;
		}
	}

	/**
	 * Get all objects of desired class/interface.
	 * @param string $class desired class/interface
	 * @return array[object] objects (can be empty)
	 */
	function getAllByType($class) {
		$ids = $this->getListByType($class);
		$objs = [];
		foreach($ids as $id){
			$objs[] = $this->get($id);
		}
		return $objs;
	}

	/**
	 * Get list of objects ID of desired class/interface.
	 * @param string $class desired class/interface
	 * @return array[string] objects IDs (can be empty)
	 */
	function getListByType($class) {
		return (isset($this->class2idMap[$class])) ? $this->class2idMap[$class] : [];
	}

	/**
	 * Get the class of a defined object.
	 * @param string $id object OID
	 * @return string object class
	 * @throws ContainerException
	 */
	function getType($id) {
		if(isset($this->id2classMap[$id])) return $this->id2classMap[$id][0];
		throw new ContainerException(1, [$this->_oid, $id]);
	}

	/**
	 * Return TRUE if contains object (optionally verifiyng class)
	 * @param string $id object identifier
	 * @param string $class class/interface that object must extend/implement (optional)
	 * @return bool
	 */
	function has($id, $class=null) {
		return ( isset($this->id2classMap[$id]) && ( is_null($class) || (in_array($class,$this->id2classMap[$id])) ) ) ? true : false;
	}

	/**
	 * @return ContainerYamlParser
	 */
	protected function getYamlParser() {
		return (!is_null($this->YamlParser)) ? $this->YamlParser : $this->YamlParser = new ContainerYamlParser(array_merge((array)$this->namespace, $this->includes));
	}

	/**
	 * Set Object property using reflection
	 * @param string $k property name
	 * @param mixed $v property value
	 * @param object $Obj
	 * @param \ReflectionObject $ReflObject
	 */
	private function setProperty($k, $v, $Obj, \ReflectionObject $ReflObject) {
		if($ReflObject->hasProperty($k)) {
			$ReflProperty = $ReflObject->getProperty($k);
			$ReflProperty->setAccessible(true);
			$ReflProperty->setValue($Obj, $v);
			$ReflProperty->setAccessible(false);
		}
	}
}
