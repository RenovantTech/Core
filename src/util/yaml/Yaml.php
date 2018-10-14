<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
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
		if(!file_exists($file) && !file_exists($fileEnv)) throw new YamlException(1, [__METHOD__, $file]);

		$yaml = $yamlEnv = [];
		if(file_exists($file)) $yaml = self::_parseFile($file, $callbacks);
		if(file_exists($fileEnv)) $yamlEnv = self::_parseFile($fileEnv, $callbacks);
		$yaml = array_merge($yaml, $yamlEnv);

		if(empty($yaml)) throw new YamlException(2, [__METHOD__, $file]);
		return $section ? isset($yaml[$section]) ? $yaml[$section] : null : $yaml;
	}

	/**
	 * Iterate on YAML, casting properties with PHP boolean, integer, float, array
	 * @param mixed $yamlNode
	 * @return mixed
	 */
	static function typeCast($yamlNode) {
		// NULL
		if(is_null($yamlNode))return null;
		// BOOLEAN
		elseif(is_bool($yamlNode)) return (boolean) $yamlNode;
		// INTEGER
		elseif(is_int($yamlNode)) return (integer) $yamlNode;
		// FLOAT
		elseif(is_float($yamlNode)) return (float) $yamlNode;
		// ARRAY
		elseif(is_array($yamlNode)) {
			foreach ($yamlNode as $k => $val)
				$yamlNode[$k] = self::typeCast($val);
			return $yamlNode;
		}
		// OBJECT
		elseif(substr($yamlNode, 0, 4) == '!obj') return new CoreProxy(substr($yamlNode, 5));
		// STRING
		else return (string) $yamlNode;
	}

	/**
	 * Add support for YAML inside PHAR
	 * @param string $file YAML file path
	 * @param array $callbacks content handlers for YAML nodes
	 * @return array parsed YAML
	 */
	static protected function _parseFile($file, array $callbacks=[]) {
		if(strpos($file, 'phar://')!==false) {
			$tmp = tempnam(TMP_DIR, 'yaml-');
			file_put_contents($tmp, file_get_contents($file));
			$yaml = yaml_parse_file($tmp, 0, $n, $callbacks);
			unlink($tmp);
		} else $yaml = yaml_parse_file($file, 0, $n, $callbacks);
		return $yaml;
	}
}
