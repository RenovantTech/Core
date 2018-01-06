<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\util\yaml;
use const metadigit\core\{BASE_DIR, ENVIRONMENT, TMP_DIR, SYS_YAML};
use const metadigit\core\trace\T_DEPINJ;
use metadigit\core\sys,
	metadigit\core\CoreProxy;
/**
 * YAML Parser
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Yaml {

	const TYPE_NULL		= 0;
	const TYPE_BOOLEAN	= 1;
	const TYPE_INTEGER	= 2;
	const TYPE_FLOAT	= 3;
	const TYPE_STRING	= 4;
	const TYPE_ARRAY	= 5;
	const TYPE_OBJ		= 6;

	/**
	 * YAML Context parser utility, supporting PHAR & ENVIRONMENT switch
	 * @param string $namespace Context namespace
	 * @param string|null $section optional YAML section to be parsed
	 * @param array $callbacks content handlers for YAML nodes
	 * @return mixed|null
	 * @throws YamlException
	 */
	static function parseContext($namespace, $section=null, array $callbacks=[]) {
		$dirName = sys::info($namespace.'.Context', sys::INFO_PATH_DIR);
		if($namespace == 'sys')
			$yamlPath = SYS_YAML;
		elseif (empty($dirName))
			$yamlPath = BASE_DIR . $namespace . '-context.yml';
		else
			$yamlPath = $dirName . DIRECTORY_SEPARATOR . 'context.yml';
		sys::trace(LOG_DEBUG, T_DEPINJ, $namespace, null, __METHOD__);
		if(!file_exists($yamlPath)) throw new YamlException(1, [__METHOD__, $yamlPath]);
		return Yaml::parseFile($yamlPath, $section, $callbacks);
	}

	/**
	 * YAML parser utility, supporting PHAR & ENVIRONMENT switch
	 * @param string $file YAML file path
	 * @param string|null $section optional YAML section to be parsed
	 * @param array $callbacks content handlers for YAML nodes
	 * @return mixed|null
	 * @throws YamlException
	 */
	static function parseFile($file, $section=null, array $callbacks=[]) {
		sys::trace(LOG_DEBUG, T_DEPINJ, $file, null, __METHOD__);
		$fileEnv = str_replace(['.yml','.yaml'], ['.'.ENVIRONMENT.'.yml', '.'.ENVIRONMENT.'.yaml'], $file);
		if(file_exists($fileEnv)) $file = $fileEnv;
		elseif(!file_exists($file)) throw new YamlException(1, [__METHOD__, $file]);
		if(strpos($file, 'phar://')!==false) {
			$tmp = tempnam(TMP_DIR, 'yaml-');
			file_put_contents($tmp, file_get_contents($file));
			$yaml = yaml_parse_file($tmp, 0, $n, $callbacks);
			unlink($tmp);
		} else $yaml = yaml_parse_file($file, 0, $n, $callbacks);
		if($yaml==false) throw new YamlException(2, [__METHOD__, $file]);
		return $section ? isset($yaml[$section]) ? $yaml[$section] : null : $yaml;
	}

	/**
	 * Iterate on YAML, casting properties with PHP boolean, integer, float, array
	 * @param mixed $yamlNode
	 * @return mixed
	 */
	static function typeCast($yamlNode) {
		switch (self::parseType($yamlNode)) {
			case self::TYPE_NULL:
				return null; break;
			case self::TYPE_BOOLEAN:
				return (boolean) $yamlNode; break;
			case self::TYPE_INTEGER:
				return (integer) $yamlNode; break;
			case self::TYPE_FLOAT:
				return (float) $yamlNode; break;
			case self::TYPE_ARRAY:
				foreach ($yamlNode as $k => $val) {
					$yamlNode[$k] = self::typeCast($val);
				}
				return $yamlNode;
			case self::TYPE_OBJ:
				return new CoreProxy(substr($yamlNode, 5));
			default:
				return (string) $yamlNode;
		}
	}

	static function parseType($yamlNode) {
		if(is_null($yamlNode)) return self::TYPE_NULL;
		elseif(is_bool($yamlNode)) return self::TYPE_BOOLEAN;
		elseif(is_int($yamlNode)) return self::TYPE_INTEGER;
		elseif(is_float($yamlNode)) return self::TYPE_FLOAT;
		elseif(is_array($yamlNode)) return self::TYPE_ARRAY;
		elseif(substr($yamlNode, 0, 4) == '!obj') return self::TYPE_OBJ;
		else return self::TYPE_STRING;
	}
}
