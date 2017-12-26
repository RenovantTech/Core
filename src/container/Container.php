<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\container;
use const metadigit\core\trace\T_DEPINJ;
use metadigit\core\sys;
/**
 * Dependency Injection Container
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Container {
	use \metadigit\core\CoreTrait;
	const ACL_SKIP = true;

	const FAILURE_EXCEPTION	= 1;
	const FAILURE_SILENT	= 2;

	/** Mapping between objects IDs and their parent classes and interfaces.
	 * @var array */
	protected $id2classMap = [];
	/** Mapping between classes and objects IDs.
	 * @var array */
	protected $class2idMap = [];
	/** initialized namespaces
	 * @var array */
	protected $namespaces = [];
	/** Array of instantiated objects (to avoid replication)
	 * @var array */
	protected $objects = [];

	/**
	 * Initialize namespace
	 * @param string $namespace Container namespace
	 * @throws ContainerException
	 */
	function init($namespace) {
		if(in_array($namespace, $this->namespaces)) return;
		$this->namespaces[] = $namespace;
		list($id2classMap, $class2idMap) = ContainerYamlParser::parseNamespace($namespace);
		$this->id2classMap = array_merge($this->id2classMap, $id2classMap);
		$this->class2idMap = array_merge($this->class2idMap, $class2idMap);
	}

	/**
	 * Get an object
	 * @param string $id           object OID
	 * @param string $class        required object class
	 * @param integer $failureMode failure mode when the object does not exist
	 * @return object
	 * @throws ContainerException
	 * @throws \metadigit\core\util\yaml\YamlException
	 */
	function get($id, $class=null, $failureMode=self::FAILURE_EXCEPTION) {
		sys::trace(LOG_DEBUG, T_DEPINJ, 'GET '.$id, null, $this->_);
		if(isset($this->objects[$id]) && (is_null($class) || $this->objects[$id] instanceof $class)) return $this->objects[$id];
		try {
			$namespace = substr($id, 0, strrpos($id, '.'));
			if(!in_array($namespace, $this->namespaces)) $this->init($namespace);
			if(!$this->has($id)) throw new ContainerException(1, [$this->_, $id]);
			if(!$this->has($id, $class)) throw new ContainerException(2, [$this->_, $id, $class]);
			list($class, $args, $properties) = ContainerYamlParser::parseObject($id);
			$this->objects[$id] = $Obj = ObjBuilder::build($id, $class, $args, $properties);
			sys::cache('sys')->set($id, $Obj);
			return $Obj;
		} catch(ContainerException $Ex) {
			if($failureMode==self::FAILURE_SILENT) return null;
			throw $Ex;
		}
	}

	/**
	 * Get all objects of desired class/interface.
	 * @param string $class desired class/interface
	 * @return object[] objects (can be empty)
	 * @throws ContainerException
	 * @throws \metadigit\core\util\yaml\YamlException
	 */
	function getAllByType($class) {
		$ids = $this->getListByType($class);
		$_ = [];
		foreach($ids as $id){
			$_[] = $this->get($id);
		}
		return $_;
	}

	/**
	 * Get list of objects ID of desired class/interface.
	 * @param string $class desired class/interface
	 * @return array[string] objects IDs (can be empty)
	 */
	function getListByType($class) {
		return $this->class2idMap[$class] ?: [];
	}

	/**
	 * Get the class of a defined object.
	 * @param string $id object OID
	 * @return string object class
	 * @throws ContainerException
	 */
	function getType($id) {
		if(isset($this->id2classMap[$id])) return $this->id2classMap[$id][0];
		throw new ContainerException(1, [$this->_, $id]);
	}

	/**
	 * Return TRUE if contains object (optionally verifying class)
	 * @param string $id object identifier
	 * @param string $class class/interface that object must extend/implement (optional)
	 * @return bool
	 */
	function has($id, $class=null) {
		return ( isset($this->id2classMap[$id]) && ( is_null($class) || (in_array($class,$this->id2classMap[$id])) ) ) ? true : false;
	}
}
