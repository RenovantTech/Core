<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core;
use const metadigit\core\trace\T_INFO;
/**
 * System bootstrap helper
 * @internal
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class sysBoot extends sys {

	/**
	 * Framework bootstrap on first launch (or cache missing)
	 * @return array
	 */
	static function boot() {
		self::trace(LOG_DEBUG, T_INFO, null, null, __METHOD__);
		self::log('sys bootstrap', LOG_INFO, 'kernel');
		// directories
		if(!defined('\metadigit\core\PUBLIC_DIR') && PHP_SAPI!='cli') die(SysException::ERR21);
		if(!defined('\metadigit\core\BASE_DIR')) die(SysException::ERR22);
		if(!defined('\metadigit\core\DATA_DIR')) die(SysException::ERR23);
		if(!is_writable(DATA_DIR)) die(SysException::ERR24);
		// DATA_DIR
		if(!file_exists(ASSETS_DIR)) mkdir(ASSETS_DIR, 0770, true);
		if(!file_exists(BACKUP_DIR)) mkdir(BACKUP_DIR, 0770, true);
		if(!file_exists(CACHE_DIR)) mkdir(CACHE_DIR, 0770, true);
		if(!file_exists(LOG_DIR)) mkdir(LOG_DIR, 0770, true);
		if(!file_exists(TMP_DIR)) mkdir(TMP_DIR, 0770, true);
		if(!file_exists(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0770, true);

		$Sys = new sys();

		$config = self::yaml(SYS_YAML);

		// APPS HTTP/CLI
		$Sys->apps['HTTP'] = $config['apps'];
		$Sys->apps['CLI'] = $config['cli'];

		// namespaces
		$namespaces = self::$namespaces;
		foreach($config['namespaces'] as $k => $dir) {
			$dir = rtrim($dir,DIRECTORY_SEPARATOR);
			if(substr($dir,0,7)=='phar://') {
				if($dir[7]!='/') $dir = 'phar://'.BASE_DIR.substr($dir,7);
				preg_match('/^phar:\/\/([0-9a-zA-Z._\-\/]+.phar)/', $dir, $matches);
				include($matches[1]);
			} else {
				if($dir[0]!='/') $dir = BASE_DIR.$dir;
			}
			$namespaces[$k] = $dir;
		}

		// constants
		if(is_array($config['constants'])) $Sys->constants = $config['constants'];

		// settings
		$Sys->settings = array_replace($Sys->settings, $config['settings']);

		// ACL service
		if(is_array($config['acl'])) $Sys->acl = array_merge($Sys->acl, $config['acl']);

		// Cache service
		if(is_array($config['cache'])) $Sys->cache = array_merge($config['cache'], $Sys->cache);

		// DB service
		if(is_array($config['database'])) $Sys->pdo = array_merge($config['database'], $Sys->pdo);
		foreach ($Sys->pdo as $id => $conf) {
			$Sys->pdo[$id] = array_merge(['user'=>null, 'pwd'=>null, 'options'=>[]], $conf);
		}

		// Log service
		if(is_array($config['log'])) $Sys->log = $config['log'];

		// write into CACHE_DIR
		file_put_contents(TMP_DIR.'core-sys', '<?php $Sys=unserialize(\''.serialize($Sys).'\'); $namespaces='.var_export($namespaces,true).';', LOCK_EX);
		rename(TMP_DIR.'core-sys', self::CACHE_FILE);

		return [$Sys, $namespaces];
	}
}
