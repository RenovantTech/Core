<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core;
use const metadigit\core\TMP_DIR;
use const metadigit\core\trace\{T_AUTOLOAD, T_DB, T_INFO};
use metadigit\core\trace\Tracer;

/**
 * __autoload() implementation
 * @param string $class class name
 */
function autoload($class) {
	list($namespace, $className, $dir, $file) = Kernel::parseClassName($class);
	if(@file_exists($file = $dir.'/'.$file.'.php')) {
		trace(LOG_DEBUG, T_AUTOLOAD, $class, null, __FUNCTION__);
		require($file);
		if(class_exists($class,0) || interface_exists($class,0) || trait_exists($class,0)) return;
	}
	trigger_error('FAILED loading '.$class, E_USER_ERROR);
}
spl_autoload_register(__NAMESPACE__.'\autoload');

/**
 * ACL helper
 * @return acl\ACL
 */
function acl() {
	static $ACL;
	if(!isset($ACL) && !$ACL = cache('kernel')->get('ACL')) {
		$cnf = Kernel::conf(Kernel::CONFIG_ACL);
		$ACL = new acl\ACL($cnf['config']['database'], $cnf['config']['tables']);
		cache('kernel')->set('ACL', $ACL);
	}
	return $ACL;
}

/**
 * Cache helper
 * @param string $id Cache ID, default "system"
 * @return cache\CacheInterface
 */
function cache($id='system') {
	static $_ = [];
	if(!isset($_[$id])) {
		$cnf = Kernel::conf(Kernel::CONFIG_CACHE)[$id];
		$RefClass = new \ReflectionClass($cnf['class']);
		$params = ($cnf['params']) ? array_merge(['id'=>$id], $cnf['params']) : ['id'=>$id];
		$Cache = $RefClass->newInstanceArgs($params);
		$_[$id] = $Cache;
	}
	return $_[$id];
}

/**
 * PDO helper
 * @param string $id database ID, default "master"
 * @return db\PDO shared PDO instance
 */
function pdo($id='master') {
	static $_ = [];
	if(!isset($_[$id])) {
		$cnf = array_merge(['user'=>null, 'pwd'=>null, 'options'=>[]], Kernel::conf(Kernel::CONFIG_PDO)[$id]);
		trace(LOG_INFO, T_DB, sprintf('open [%s] %s', $id, $cnf['dns']), null, __METHOD__);
		$pdo = @new db\PDO($cnf['dns'], $cnf['user'], $cnf['pwd'], $cnf['options'], $id);
		$_[$id] = $pdo;
	}
	return $_[$id];
}

/**
 * Trace helper
 * @param integer $level trace level, use a LOG_* constant value
 * @param integer $type trace type, use a T_* constant value
 * @param string $msg the trace message
 * @param mixed $data the trace data
 * @param string $function the calling object method
 * @return void
 */
function trace($level=LOG_DEBUG, $type=T_INFO, $msg=null, $data=null, $function=null) {
	Tracer::trace($level, $type, $msg, $data, $function);
}

/**
 * YAML parser utility, supporting PHAR & ENVIRONMENT switch
 * @param string $file YAML file path
 * @param string|null $section optional YAML section to be parsed
 * @return array
 * @throws Exception
 */
function yaml($file, $section=null) {
	$fileEnv = str_replace(['.yml','.yaml'], ['.'.ENVIRONMENT.'.yml', '.'.ENVIRONMENT.'.yaml'], $file);
	if(file_exists($fileEnv)) $file = $fileEnv;
	elseif(!file_exists($file)) throw new Exception(__FUNCTION__.' YAML not found: '.$file);
	if(strpos($file, 'phar://')!==false) {
		$tmp = tempnam(TMP_DIR, 'yaml-');
		file_put_contents($tmp, file_get_contents($file));
		$YAML = yaml_parse_file($tmp);
		unlink($tmp);
	} else $YAML = yaml_parse_file($file);
	return ($section) ? $YAML[$section] : $YAML;
}
