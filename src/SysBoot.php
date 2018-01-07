<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core;
use const metadigit\core\trace\T_INFO;
use metadigit\core\util\yaml\Yaml;
/**
 * System bootstrap helper
 * @author Daniele Sciacchitano <dan@metadigit.it>
 * @internal
 */
class SysBoot extends sys {

	/**
	 * Framework bootstrap on first launch (or cache missing)
	 * @throws util\yaml\YamlException
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

		$config = Yaml::parseFile(SYS_YAML);

		// APPS HTTP/CLI
		self::$Sys->cnfApps['HTTP'] = $config['apps'];
		self::$Sys->cnfApps['CLI'] = $config['cli'];

		// namespaces
		foreach($config['namespaces'] as $k => $dir) {
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
		if(is_array($config['constants'])) self::$Sys->cnfConstants = $config['constants'];

		// settings
		self::$Sys->cnfSettings = array_replace(self::$Sys->cnfSettings, $config['settings']);

		// ACL service
		if(is_array($config['acl'])) self::$Sys->cnfAcl = array_merge(self::$Sys->cnfAcl, $config['acl']);

		// AUTH service
		if(is_array($config['auth'])) self::$Sys->cnfAuth = array_merge(self::$Sys->cnfAuth, $config['auth']);

		// Cache service
		if(is_array($config['cache'])) self::$Sys->cnfCache = array_merge($config['cache'], self::$Sys->cnfCache);
//		self::$Cache = (new Container())->build('sys.cache.SYS', self::$Sys->cnfCache['sys']['class'], self::$Sys->cnfCache['sys']['constructor'], self::$Sys->cnfCache['sys']['properties']);

		// DB service
		if(is_array($config['database'])) self::$Sys->cnfPdo = array_merge($config['database'], self::$Sys->cnfPdo);
		foreach (self::$Sys->cnfPdo as $id => $conf) {
			self::$Sys->cnfPdo[$id] = array_merge(['user'=>null, 'pwd'=>null, 'options'=>[]], $conf);
		}

		// Log service
		if(is_array($config['log'])) self::$Sys->cnfLog = $config['log'];

		// Trace service
		self::$Sys->cnfTrace = array_replace(self::$Sys->cnfTrace, $config['trace']);
		if(is_string(self::$Sys->cnfTrace['level'])) self::$Sys->cnfTrace['level'] = constant(self::$Sys->cnfTrace['level']);

		// write into CACHE_DIR
		$Sys = serialize(self::$Sys);
		$namespaces = var_export(self::$namespaces,true);
		$cache = <<<CACHE
<?php
self::\$Sys = unserialize('$Sys');
self::\$namespaces = $namespaces;
CACHE;
		file_put_contents(TMP_DIR.'core-sys', $cache, LOCK_EX);
		rename(TMP_DIR.'core-sys', self::CACHE_FILE);
	}
}
