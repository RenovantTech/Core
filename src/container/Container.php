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
	const YAML_OBJ_SKELETON = [
		'class' => \stdClass::class,
		'constructor' => [],
		'properties' => []
	];

	/** Mapping between services IDs and their parent classes and interfaces.
	 * @var array */
	protected $id2classMap = [];
	/** Mapping between classes and services IDs.
	 * @var array */
	protected $class2idMap = [];
	/** initialized namespaces
	 * @var array */
	protected $namespaces = [];
	/** Array of instantiated services (to avoid replication)
	 * @var array */
	protected $services = [];

	/**
	 * Build an Object using reflection
	 * @param string $id Object ID
	 * @param string $class Object class
	 * @param array|null $args constructor args
	 * @param array $properties Object properties
	 * @return object
	 * @internal
	 */
	function build($id, $class, array $args=null, array $properties=[]) {
		$RClass = new \ReflectionClass($class);
		$Obj = $RClass->newInstanceWithoutConstructor();
		$RObj = new \ReflectionObject($Obj);
		self::setProperty('_', $id, $Obj, $RObj);
		foreach ($properties as $k=>$v)
			self::setProperty($k, $v, $Obj, $RObj);
		if($RConstructor = $RClass->getConstructor())
			$RConstructor->invokeArgs($Obj, $args);
		return $Obj;
	}

	/**
	 * Initialize namespace
	 * @param string $namespace Container namespace
	 * @param array|null $containerMaps
	 * @throws ContainerException
	 */
	function init($namespace, array $containerMaps=null) {
		if(in_array($namespace, $this->namespaces)) return;
		//sys::trace(LOG_DEBUG, T_DEPINJ, $namespace, null, 'sys.Container->init');
		$this->namespaces[] = $namespace;
		$maps = $containerMaps ?? ContainerYamlParser::parseNamespace($namespace);
		$this->id2classMap = array_merge($this->id2classMap, $maps['id2class']);
		$this->class2idMap = array_merge($this->class2idMap, $maps['class2id']);
		if(!$containerMaps) sys::cache('sys')->set($namespace.'#services', $maps['services']);
	}

	/**
	 * Get an object
	 * @param string $id           object OID
	 * @param string $class        required object class
	 * @param integer $failureMode failure mode when the object does not exist
	 * @return object
	 * @throws ContainerException
	 */
	function get($id, $class=null, $failureMode=self::FAILURE_EXCEPTION) {
		sys::trace(LOG_DEBUG, T_DEPINJ, $id, null, 'sys.Container->get');
		if(isset($this->services[$id]) && (is_null($class) || $this->services[$id] instanceof $class)) return $this->services[$id];
		try {
			$namespace = substr($id, 0, strrpos($id, '.'));
			if(!in_array($namespace, $this->namespaces)) $this->init($namespace);
			if(!$this->has($id)) throw new ContainerException(1, [$this->_, $id]);
			if(!$this->has($id, $class)) throw new ContainerException(2, [$this->_, $id, $class]);
			if(!$Obj = sys::cache('sys')->get($id)) {
				$obj = sys::cache('sys')->get($namespace.'#services')[$id];
				$Obj = $this->build($id, $obj['class'], $obj['constructor'], $obj['properties']);
				sys::cache('sys')->set($id, $Obj);
			}
			return $this->services[$id] = $Obj;
		} catch(ContainerException $Ex) {
			if($failureMode==self::FAILURE_SILENT) return null;
			throw $Ex;
		}
	}

	/**
	 * Get all services of desired class/interface.
	 * @param string $class desired class/interface
	 * @return object[] services (can be empty)
	 * @throws ContainerException
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
	 * Get list of services ID of desired class/interface.
	 * @param string $class desired class/interface
	 * @return array[string] services IDs (can be empty)
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

	/**
	 * Set Object property using reflection
	 * @param string $k property name
	 * @param mixed $v property value
	 * @param object $Obj
	 * @param \ReflectionObject $RObject
	 */
	static protected function setProperty($k, $v, $Obj, \ReflectionObject $RObject) {
		if($RObject->hasProperty($k)) {
			$RProperty = $RObject->getProperty($k);
			$RProperty->setAccessible(true);
			$RProperty->setValue($Obj, $v);
			$RProperty->setAccessible(false);
		}
	}
}
