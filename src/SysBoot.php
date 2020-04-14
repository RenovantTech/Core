<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core;
use const renovant\core\cache\OBJ_ID_PREFIX;
use const renovant\core\trace\T_INFO;
use renovant\core\container\Container,
	renovant\core\util\yaml\Yaml;
/**
 * System bootstrap helper
 * @author Daniele Sciacchitano <dan@renovant.tech>
 * @internal
 */
class SysBoot extends sys {

	const SYS_CACHE_DEFAULT_CONFIG = [
		'class' => 'renovant\core\cache\SqliteCache',
		'constructor' => ['sys', 'cache', true]
	];

	/**
	 * Framework bootstrap on first launch (or cache missing)
	 * @throws util\yaml\YamlException
	 * @throws \ReflectionException
	 */
	static function boot() {
		self::trace(LOG_DEBUG, T_INFO, null, null, __METHOD__);
		self::log('sys bootstrap', LOG_INFO, 'kernel');
		// directories
		if(!defined(__NAMESPACE__.'\PUBLIC_DIR') && PHP_SAPI!='cli') die(SysException::ERR21);
		if(!defined(__NAMESPACE__.'\BASE_DIR')) die(SysException::ERR22);
		if(!defined(__NAMESPACE__.'\DATA_DIR')) die(SysException::ERR23);
		if(!is_writable(DATA_DIR)) die(SysException::ERR24);
		// DATA_DIR
		if(!file_exists(ASSETS_DIR)) mkdir(ASSETS_DIR, 0770, true);
		if(!file_exists(BACKUP_DIR)) mkdir(BACKUP_DIR, 0770, true);
		if(!file_exists(CACHE_DIR)) mkdir(CACHE_DIR, 0770, true);
		if(!file_exists(LOG_DIR)) mkdir(LOG_DIR, 0770, true);
		if(!file_exists(RUN_DIR)) mkdir(RUN_DIR, 0770, true);
		if(!file_exists(TMP_DIR)) mkdir(TMP_DIR, 0770, true);
		if(!file_exists(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0770, true);
		// CLI paths
		if(!defined(__NAMESPACE__.'\CLI_BOOTSTRAP')) die(SysException::ERR25);
		if(!defined(__NAMESPACE__.'\CLI_PHP_BIN')) die(SysException::ERR26);

		self::$Sys = new sys();

		$config = array_merge_recursive([
			'sys' => [
				'apps' => [],
				'cli' => [],
				'namespaces' => [],
				'constants' => [],
				'settings' => [],
				'cache' => [],
				'database' => [],
				'log' => [],
				'trace' => [],
				'services' => []
			]
		], Yaml::parseFile(BASE_DIR.SYS_YAML));

		// APPS HTTP/CLI
		self::$Sys->cnfApps['HTTP'] = $config['sys']['apps'];
		self::$Sys->cnfApps['CLI'] = $config['sys']['cli'];

		// namespaces
		foreach($config['sys']['namespaces'] as $k => $dir) {
			$dir = rtrim($dir,DIRECTORY_SEPARATOR);
			if(substr($dir,0,7)=='phar://') {
				if($dir[7]!='/') $dir = 'phar://'.BASE_DIR.substr($dir,7);
				preg_match('/^phar:\/\/([0-9a-zA-Z._\-\/]+.phar)/', $dir, $matches);
				include($matches[1]);
			} else {
				if($dir[0]!='/') $dir = BASE_DIR.$dir;
			}
			self::$namespaces[$k] = $dir;
		}

		// constants
		if(is_array($config['sys']['constants']))
			self::$Sys->cnfConstants = $config['sys']['constants'];

		// settings
		if(is_array($config['sys']['settings']))
			self::$Sys->cnfSettings = array_replace(self::$Sys->cnfSettings, $config['sys']['settings']);

		// Cache service
		self::$Sys->cnfCache[SYS_CACHE] = self::SYS_CACHE_DEFAULT_CONFIG;
		if(is_array($config['sys']['cache']))
			self::$Sys->cnfCache = array_merge(self::$Sys->cnfCache, $config['sys']['cache']);
		foreach (self::$Sys->cnfCache as $id => $conf)
			self::$Sys->cnfCache[$id] = array_merge(Container::YAML_OBJ_SKELETON, $conf);
		$sysCacheConf = self::$Sys->cnfCache[SYS_CACHE];
		unset(self::$Sys->cnfCache[SYS_CACHE]);

		// DB service
		if(is_array($config['sys']['database']))
			self::$Sys->cnfPdo = array_merge($config['sys']['database'], self::$Sys->cnfPdo);
		foreach (self::$Sys->cnfPdo as $id => $conf)
			self::$Sys->cnfPdo[$id] = array_merge(['user'=>null, 'pwd'=>null, 'options'=>[]], $conf);

		// Log service
		if(is_array($config['sys']['log'])) self::$Sys->cnfLog = $config['sys']['log'];

		// Trace service
		if(is_array($config['sys']['trace']))
			self::$Sys->cnfTrace = array_merge(self::$Sys->cnfTrace, $config['sys']['trace']);
		if(is_string(self::$Sys->cnfTrace['level']))
			self::$Sys->cnfTrace['level'] = constant(self::$Sys->cnfTrace['level']);

		// sys services override
		if(isset($config['sys']['services']))
			self::$Sys->cnfServices = array_merge(self::$Sys->cnfServices, $config['sys']['services']);

		// initialize
		self::$Cache = (new Container())->build(OBJ_ID_PREFIX.strtoupper(SYS_CACHE), $sysCacheConf['class'], $sysCacheConf['constructor'], $sysCacheConf['properties']);

		// write into SYS_YAML_CACHE file
		$Sys = serialize(self::$Sys);
		$namespaces = var_export(self::$namespaces,true);
		$Cache = serialize(self::$Cache);
		$cache = <<<CACHE
<?php
self::\$Sys = unserialize('$Sys');
self::\$namespaces = $namespaces;
self::\$Cache = unserialize('$Cache');
CACHE;
		file_put_contents(TMP_DIR.'core-sys', $cache, LOCK_EX);
		rename(TMP_DIR.'core-sys', self::SYS_YAML_CACHE);
	}
}
