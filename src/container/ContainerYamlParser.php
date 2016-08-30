<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\container;
use function metadigit\core\trace;
use metadigit\core\CoreProxy,
	metadigit\core\Kernel;
/**
 * Dependency Injection ContainerParser
 * @internal
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class ContainerYamlParser {
	use \metadigit\core\CoreTrait;

	/** Included Container namespaces
	 * @var array */
	protected $namespaces = [];
	/** Container YAML path
	 * @var string */
	protected $yamlPath;
	/** Container YAML
	 * @var array */
	protected $YAML;

	/**
	 * @param array $namespaces Container namespaces
	 * @throws ContainerException
	 */
	function __construct(array $namespaces) {
		$this->_oid = $namespaces[0].'.ContainerParser';
		list($namespace2, $className, $dirName, $fileName) = Kernel::parseClassName(str_replace('.', '\\', $this->_oid));
		if (empty($dirName))
			$this->yamlPath = \metadigit\core\BASE_DIR . $namespaces[0] . '-context.yaml';
		else
			$this->yamlPath = $dirName . DIRECTORY_SEPARATOR . 'context.yaml';
		if(!file_exists($this->yamlPath)) throw new ContainerException(11, [$this->_oid, $this->yamlPath]);
		$this->YAML = yaml_parse_file($this->yamlPath, 0, $n, [
			'!obj' => function($value, $tag, $flags) {
				return '!obj '.$value;
			}
		]);
		// @TODO verify YAML content
		// if(!XMLValidator::schema($xmlPath, __DIR__.'/Container.xsd')) throw new ContainerException(12, [$xmlPath]);
		sort($namespaces);
		$this->namespaces = $namespaces;
	}

	/**
	 * @param array $id2classMap
	 * @param array $class2idMap
	 * @internal
	 */
	function parseMaps(array &$id2classMap, array &$class2idMap) {
		if(is_array($this->YAML['objects'])) {
			TRACE and trace(LOG_DEBUG, TRACE_DEPINJ, '[START] parsing Container YAML', null, $this->_oid);
			$id2classMap = $class2idMap = [];
			$filter = function($v) {
				if(in_array($v, ['\metadigit\core\BaseObject'])) return false;
				if((boolean)strpos($v,'Abstract')) return false;
				if((boolean)strpos('-'.$v,'xi')) return false;
				return true;
			};
			foreach($this->YAML['objects'] as $id => $objYAML) {
				$parents = array_values(class_parents($objYAML['class']));
				$interfaces = array_values(class_implements($objYAML['class']));
				$all_classes = array_merge([$objYAML['class']], $parents, $interfaces);
				$all_classes = array_filter($all_classes, $filter);
				$id2classMap[$id] = $all_classes;
				foreach($all_classes as $class){
					$class2idMap[$class][] = $id;
				}
			}
			TRACE and trace(LOG_DEBUG, TRACE_DEPINJ, '[END] Container ready', null, $this->_oid);
		}
	}

	/**
	 * Return object constructor args
	 * @param string $id object OID
	 * @return array constructor args
	 * @throws ContainerException
	 */
	function parseObjectConstructorArgs($id) {
		$args = [];
		if(is_array($this->YAML['objects'][$id]['constructor'])) {
			TRACE and trace(LOG_DEBUG, TRACE_DEPINJ, 'parsing constructor args for object `'.$id.'`', null, $this->_oid);
			$i = 0;
			foreach ($this->YAML['objects'][$id]['constructor'] as $yamlArg) {
				$yamlArg = trim($yamlArg);
				switch(self::parseType($yamlArg)) {
					case 'boolean':
						$args[$i] = (boolean) $yamlArg;
						break;
					case 'integer':
						$args[$i] = (integer) $yamlArg;
						break;
					case 'array':
						foreach($yamlArg as $key => $yamlItem) {
							$yamlItem = trim($yamlItem);
							switch(self::parseType($yamlItem)) {
								case 'boolean':
									$value = (boolean) $yamlItem;
									break;
								case 'integer':
									$value = (integer) $yamlItem;
									break;
								case 'object':
									$value = new CoreProxy(substr($yamlItem, 5));
									break;
								default: // string
									$value = (string) $yamlItem;
							}
							$args[$i][$key] = $value;
						}
						break;
					case 'object':
						$args[$i] = new CoreProxy(substr($yamlArg, 5));
						break;
					default: // string
						$args[$i] = (string) $yamlArg;
				}
				$i++;
			}
		}
		return $args;
	}

	/**
	 * Return object properties
	 * @param string $id object OID
	 * @return array properties
	 * @throws ContainerException
	 */
	function parseObjectProperties($id) {
		$properties = [];
		if(is_array($this->YAML['objects'][$id]['properties'])) {
			TRACE and trace(LOG_DEBUG, TRACE_DEPINJ, 'parsing properties for object `'.$id.'`', null, $this->_oid);
			foreach ($this->YAML['objects'][$id]['properties'] as $propName => $yamlProp) {
				switch (self::parseType($yamlProp)) {
					case 'boolean':
						$properties[$propName] = (boolean) $yamlProp;
						break;
					case 'integer':
						$properties[$propName] = (integer) $yamlProp;
						break;
					case 'array':
						foreach($yamlProp as $key => $yamlItem) {
							switch (self::parseType($yamlItem)) {
								case 'boolean':
									$value = (boolean) $yamlItem;
									break;
								case 'integer':
									$value = (integer) $yamlItem;
									break;
								case 'object':
									$value = new CoreProxy(substr($yamlItem, 5));
									break;
								default: // string
									$value = (string) $yamlItem;
							}
							$properties[$propName][$key] = $value;
						}
						break;
					case 'object':
						$properties[$propName] = new CoreProxy(substr($yamlProp, 5));
						break;
					default: // string
						$properties[$propName] = (string) $yamlProp;
				}
			}
		}
		return $properties;
	}

	static function parseType($yamlNode) {
		if(is_array($yamlNode)) return 'array';
		elseif(substr($yamlNode, 0, 4) == '!obj') return 'object';
		elseif(is_bool($yamlNode)) return 'boolean';
		elseif(is_numeric($yamlNode)) return 'integer';
		else return 'string';
	}
}
