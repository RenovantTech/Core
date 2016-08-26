<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\depinjection;
use function metadigit\core\trace;
use metadigit\core\CoreProxy;
/**
 * Dependency Injection ContainerParser
 * @internal
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class ContainerXmlParser {
	use \metadigit\core\CoreTrait;

	/** Included Container namespaces
	 * @var array */
	protected $namespaces = [];
	/** Container reference for ObjectProxy
	 * @var string */
	protected $proxyRef;
	/** Container XML path
	 * @var string */
	protected $xmlPath;
	/** Container XML
	 * @var \SimpleXMLElement */
	protected $XML;

	/**
	 * @param string $xmlPath XML path
	 * @param array $namespaces Container namespaces
	 * @param string $proxyRef Container ID for ObjectProxy
	 */
	function __construct($xmlPath, $namespaces, $proxyRef) {
		$this->_oid = $namespaces[0].'.ContainerParser';
		$this->proxyRef = $proxyRef;
		sort($namespaces);
		$this->namespaces = $namespaces;
		$this->xmlPath = $xmlPath;
		$this->XML = simplexml_load_string(self::parseConstants(file_get_contents($this->xmlPath)));
	}

	/**
	 * Return object class
	 * @param string $id object identifier
	 * @throws ContainerException
	 * @return string object class
	 */
	function getClass($id) {
		list($objXML) = $this->XML->xpath('/objects/object[@id=\''.$id.'\']');
		if(!$objXML) throw new ContainerException(1, [$this->xmlPath, $id]);
		return (string)$objXML['class'];
	}

	/**
	 * Return 2 maps:
	 * - object IDs => classes & interfaces
	 * - interfaces & classes => implementing/extending objects IDs
	 * @return array
	 */
	function parseMaps() {
		$id2classMap = $class2idMap = [];
		$filter = function($v) {
			if(in_array($v, ['\metadigit\core\BaseObject'])) return false;
			if((boolean)strpos($v,'Abstract')) return false;
			if((boolean)strpos('-'.$v,'xi')) return false;
			return true;
		};
		foreach($this->XML->xpath('/objects/object') as $objXML){
			$id = (string)$objXML['id'];
			$class = (string)$objXML['class'];
			$parents = array_values(class_parents($class));
			$interfaces = array_values(class_implements($class));
			$all_classes = array_merge([$class] ,$parents, $interfaces);
			$all_classes = array_filter($all_classes, $filter);
			$id2classMap[$id] = $all_classes;
			foreach($all_classes as $class){
				$class2idMap[$class][] = $id;
			}
		}
		return [$id2classMap, $class2idMap];
	}

	/**
	 * Return object constructor args
	 * @param string $id object OID
	 * @param string $containerOID calling Container OID
	 * @return array constructor args
	 * @throws ContainerException
	 */
	function parseObjectConstructorArgs($id, $containerOID) {
		TRACE and trace(LOG_DEBUG, TRACE_DEPINJ, 'parsing constructor args for object `'.$id.'`', null, $this->_oid);
		list($objXML) = $this->XML->xpath('/objects/object[@id=\''.$id.'\']');
		if(!$objXML) throw new ContainerException(1, [$containerOID, $id]);
		$args = [];
		foreach ($objXML->xpath('constructor/arg') as $xmlArg) {
			switch(self::parseType($xmlArg)) {
				case 'boolean':
					$args[(string)$xmlArg['name']] = (in_array($xmlArg, ['1','true'])) ? true : false;
					break;
				case 'integer':
					$args[(string)$xmlArg['name']] = (integer)$xmlArg;
					break;
				case 'string':
					$args[(string)$xmlArg['name']] = (string)$xmlArg;
					break;
				case 'array':
					foreach($xmlArg->children() as $xmlItem) {
						switch(self::parseType($xmlItem)) {
							case 'boolean':
								$value = (boolean)$xmlItem;
								break;
							case 'string':
								$value = (string)$xmlItem;
								break;
							case 'object':
								$value = new CoreProxy((string)$xmlItem, $this->proxyRef);
								break;
						}
						$key = ($xmlItem['key']) ? (string)$xmlItem['key'] : null;
						if(is_null($key)) $args[(string)$xmlArg['name']][] = $value;
						else $args[(string)$xmlArg['name']][$key] = $value;
					}
					break;
				case 'map':
					$map = [];
					foreach($xmlArg->children() as $xmlMap) {
						foreach($xmlMap->children() as $xmlItem) {
							$map[(string)$xmlMap['key']][(string)$xmlItem['key']] = (string)$xmlItem;
						}
					}
					$args[(string)$xmlArg['name']] = $map;
					break;
				case 'object':
					$id = (string)$xmlArg;
					foreach($this->namespaces as $ns) {
						if(strpos($id, $ns.'.') === 0) $namespace = $ns;
					}
					$args[(string)$xmlArg['name']] = new CoreProxy($id, $this->proxyRef);
					break;
			}
		}
		return $args;
	}

	/**
	 * Return object properties
	 * @param string $id object OID
	 * @param string $containerOID calling Container OID
	 * @return array properties
	 * @throws ContainerException
	 */
	function parseObjectProperties($id, $containerOID) {
		TRACE and trace(LOG_DEBUG, TRACE_DEPINJ, 'parsing properties for object `'.$id.'`', null, $this->_oid);
		list($objXML) = $this->XML->xpath('/objects/object[@id=\''.$id.'\']');
		if(!$objXML) throw new ContainerException(1, [$containerOID, $id]);
		$properties = [];
		foreach ($objXML->xpath('properties/property') as $xmlProp) {
			switch (self::parseType($xmlProp)) {
				case 'boolean':
					$properties[(string)$xmlProp['name']] = (in_array($xmlProp, ['1','true'])) ? true : false;
					break;
				case 'integer':
					$properties[(string)$xmlProp['name']] = (integer)$xmlProp;
					break;
				case 'string':
					$properties[(string)$xmlProp['name']] = (string)$xmlProp;
					break;
				case 'array':
					foreach($xmlProp->children() as $xmlItem) {
						switch (self::parseType($xmlItem)) {
							case 'boolean':
								$value = (boolean)$xmlItem;
								break;
							case 'string':
								$value = (string)$xmlItem;
								break;
							case 'object':
								$value = new CoreProxy((string)$xmlItem, $this->proxyRef);
								break;
						}
						$key = ($xmlItem['key']) ? (string)$xmlItem['key'] : null;
						if(is_null($key)) $properties[(string)$xmlProp['name']][] = $value;
						else $properties[(string)$xmlProp['name']][$key] = $value;
					}
					break;
				case 'map':
					$map = [];
					foreach($xmlProp->children() as $xmlMap) {
						foreach($xmlMap->children() as $xmlItem) {
							$map[(string)$xmlMap['key']][(string)$xmlItem['key']] = (string)$xmlItem;
						}
					}
					$properties[(string)$xmlProp['name']] = $map;
					break;
				case 'object':
					$id = (string)$xmlProp;
					foreach($this->namespaces as $ns) {
						if(strpos($id, $ns.'.') === 0) $namespace = $ns;
					}
					$properties[(string)$xmlProp['name']] = new CoreProxy($id, $this->proxyRef);
					break;
			}
		}
		return $properties;
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

	static function parseType($xmlNode) {
			if(isset($xmlNode['type'])) return $xmlNode['type'];
			elseif(is_numeric((string)$xmlNode)) return 'integer';
			else return 'string';
	}
}
