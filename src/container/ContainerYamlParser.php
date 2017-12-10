<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\container;
use const metadigit\core\trace\T_DEPINJ;
use metadigit\core\sys,
	metadigit\core\util\yaml\YamlParser;
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
		$this->_ = $namespaces[0].'.ContainerParser';
		$dirName = sys::info($this->_, sys::INFO_PATH_DIR);
		if (empty($dirName))
			$this->yamlPath = \metadigit\core\BASE_DIR . $namespaces[0] . '-context.yml';
		else
			$this->yamlPath = $dirName . DIRECTORY_SEPARATOR . 'context.yml';
		if(!file_exists($this->yamlPath)) throw new ContainerException(11, [$this->_, $this->yamlPath]);
		$this->YAML = sys::yaml($this->yamlPath, null, [
			'!obj' => function($value, $tag, $flags) {
				return '!obj '.$value;
			}
		]);
		// @TODO verify YAML content
		if(
			!is_array($this->YAML) ||
			(isset($this->YAML['objects']) && !is_array($this->YAML['objects']))
		) throw new ContainerException(12, [$this->yamlPath]);
		sort($namespaces);
		$this->namespaces = $namespaces;
	}

	/**
	 * @param array $id2classMap
	 * @param array $class2idMap
	 * @internal
	 */
	function parseMaps(array &$id2classMap, array &$class2idMap) {
		if(isset($this->YAML['objects'])) {
			sys::trace(LOG_DEBUG, T_DEPINJ, '[START] parsing Container YAML', null, $this->_);
			$id2classMap = $class2idMap = [];
			$filter = function($v) {
				if((boolean)strpos($v,'Abstract')) return false;
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
			sys::trace(LOG_DEBUG, T_DEPINJ, '[END] Container ready', null, $this->_);
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
		if(isset($this->YAML['objects'][$id]['constructor']) && is_array($this->YAML['objects'][$id]['constructor'])) {
			sys::trace(LOG_DEBUG, T_DEPINJ, 'parsing constructor args for object `'.$id.'`', null, $this->_);
			$args = (new YamlParser())->typeCast($this->YAML['objects'][$id]['constructor']);
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
		if(isset($this->YAML['objects'][$id]['properties']) && is_array($this->YAML['objects'][$id]['properties'])) {
			sys::trace(LOG_DEBUG, T_DEPINJ, 'parsing properties for object `'.$id.'`', null, $this->_);
			$properties = (new YamlParser())->typeCast($this->YAML['objects'][$id]['properties']);
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
