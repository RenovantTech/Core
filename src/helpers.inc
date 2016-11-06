<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core;

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
		$cnf = Kernel::conf(Kernel::CONFIG_PDO)[$id];
		TRACE and Kernel::trace(LOG_INFO, TRACE_DB, sprintf('open [%s] %s', $id, $cnf['dns']), null, __METHOD__);
		$pdo = new db\PDO($cnf['dns'], @$cnf['user'], @$cnf['pwd'], @$cnf['options']?:[], $id);
		$_[$id] = $pdo;
	}
	return $_[$id];
}

/**
 * Trace helper
 * @param integer $level trace level, use a LOG_? constant value
 * @param integer $type trace type, use a TRACE_? constant value
 * @param string $msg the trace message
 * @param mixed $data the trace data
 * @param string $function the calling object method
 * @return void
 */
function trace($level=LOG_DEBUG, $type=TRACE_DEFAULT, $msg=null, $data=null, $function=null) {
	Kernel::trace($level, $type, $msg, $data, $function);
}

/**
 * YAML parser utility, supporting ENVIRONMENT switch
 * @param string $file         YAML file path
 * @param string|null $section YAML section to be parsed
 * @return array
 * @throws Exception
 */
function yaml($file, $section=null) {
	$fileEnv = str_replace(['.yml','.yaml'], ['.'.ENVIRONMENT.'.yml', '.'.ENVIRONMENT.'.yaml'], $file);
	if(file_exists($fileEnv)) $YAML = yaml_parse_file($fileEnv);
	elseif(file_exists($file)) $YAML = yaml_parse_file($file);
	else throw new Exception('YAML not found: '.$file);
	return ($section) ? $YAML[$section] : $YAML;
}
