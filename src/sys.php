<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core;
use const metadigit\core\trace\{T_AUTOLOAD, T_DB, T_INFO};
use metadigit\core\context\Context,
	metadigit\core\trace\Tracer;
/**
 * System Kernel
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class sys {

	const CACHE_FILE = CACHE_DIR.'sys';
	const EVENT_INIT		= 'sys:init';
	const EVENT_SHUTDOWN	= 'sys:shutdown';
	const INFO_NAMESPACE = 1;
	const INFO_CLASS = 2;
	const INFO_PATH = 3;
	const INFO_PATH_DIR = 4;
	const INFO_PATH_FILE = 5;
	/** Namespace definitions, used by __autoload()
	 * @var array */
	static protected $namespaces = [
		__NAMESPACE__ => __DIR__
	];
	/** Current HTTP/CLI Request
	 * @var object */
	static protected $Req;
	/** Current HTTP/CLI Response
	 * @var object */
	static protected $Res;
	/** Singleton instance
	 * @var sys */
	static protected $Sys;
	/** System Context
	 * @var \metadigit\core\context\Context */
	static protected $SystemContext;
	/** ACL configurations
	 * @var array */
	protected $acl = [
		'routes' => false,
		'objects' => false,
		'orm' => false,
		'config' => [
			'database' => 'master',
			'tables' => null
		]
	];
	/** HTTP/CLI apps routing
	 * @var array */
	protected $apps;
	/** Cache configurations
	 * @var array */
	protected $cache = [
		'sys' => [
			'class' => 'metadigit\core\cache\SqliteCache',
			'params' => ['sys-cache', 'cache', true]
		]
	];
	/** Constants
	 * @var array */
	protected $constants;
	/** LogWriters configurations
	 * @var array */
	protected $log;
	/** Database PDO configurations
	 * @var array */
	protected $pdo = [
		'sys-cache' => [ 'dns' => 'sqlite:'.CACHE_DIR.'sys-cache.sqlite' ],
		'sys-trace' => [ 'dns' => 'sqlite:'.DATA_DIR.'sys-trace.sqlite' ]
	];
	/** system settings
	 * @var array */
	protected $settings = [
		'charset'		=> 'UTF-8',
		'locale'		=> 'en_US.UTF-8',
		'timeZone'		=> 'UTC'
	];

	/**
	 * System Kernel bootstrap.
	 * It has the following functions:
	 * * set user defined constants;
	 * * set global php settings (TimeZone, charset);
	 * * initialize classes auto-loading;
	 * - register error & exception handlers.
	 */
	static function init() {
		Tracer::traceFn(__METHOD__);
		self::trace(LOG_DEBUG, T_INFO);
		register_shutdown_function(function () {
			Tracer::traceFn(__METHOD__);
			//self::$SystemContext->trigger(self::EVENT_SHUTDOWN);
			//cache\SqliteCache::shutdown();
			if(PHP_SAPI != 'cli') session_write_close();
		});

		// ENVIRONMENT FIX
		if(isset($_SERVER['REDIRECT_PORT'])) $_SERVER['SERVER_PORT'] = $_SERVER['REDIRECT_PORT'];

		// environment settings
		ignore_user_abort(1);
		ini_set('upload_tmp_dir', TMP_DIR);
//		register_shutdown_function(__CLASS__.'::shutdown');

		$Sys = $namespaces = null;
		@include self::CACHE_FILE;
		if(!isset($Sys)) list($Sys, $namespaces) = sysBoot::boot();
		self::$namespaces = $namespaces;
		self::$Sys = $Sys;

		// settings
		date_default_timezone_set($Sys->settings['timeZone']);
		setlocale(LC_ALL, $Sys->settings['locale']);
		ini_set('default_charset', $Sys->settings['charset']);
		// constants
		foreach($Sys->constants as $k => $v) define($k, $v);
		// ACL
		define(__NAMESPACE__.'\ACL_ROUTES', (boolean) $Sys->acl['routes']);
		define(__NAMESPACE__.'\ACL_OBJECTS', (boolean) $Sys->acl['objects']);
		define(__NAMESPACE__.'\ACL_ORM', (boolean) $Sys->acl['orm']);

		// initialize
		self::cache('sys');
		if(ACL_ROUTES || ACL_OBJECTS || ACL_ORM) self::acl();
//		self::$SystemContext = Context::factory('system');
//		self::$SystemContext->trigger(self::EVENT_INIT);
	}

	/**
	 * Dispatch HTTP/CLI request
	 * @param string $api PHP_SAPI
	 * @throws KernelException
	 */
	static function dispatch($api=PHP_SAPI) {
		Tracer::traceFn(__METHOD__);
		self::$Req = ($api=='cli') ? new cli\Request : new http\Request;
		self::$Res = ($api=='cli') ? new cli\Response : new http\Response;
		$app = $dispatcherID = $namespace = null;
		switch($api) {
			case 'cli':
				foreach(self::$Sys->apps['CLI'] as $id => $namespace) {
					if(self::$Req->CMD(0) == $id) {
						$app = $id;
						$dispatcherID = $namespace.'.Dispatcher';
						self::$Req->setAttribute('APP_URI', trim(strstr(self::$Req->CMD(),' ')));
						break;
					};
				}
				break;
			default:
				foreach(self::$Sys->apps['HTTP'] as $id => $conf) {
					$urlPattern = '/^'.preg_quote($conf['baseUrl'],'/').'/';
					if(preg_match($urlPattern, $_SERVER['REQUEST_URI']) && $_SERVER['SERVER_PORT']==$conf['httpPort']) {
						$app = $id;
						$namespace = $conf['namespace'];
						$dispatcherID = $namespace.'.Dispatcher';
						self::$Req->setAttribute('APP_URI', str_replace($conf['baseUrl'], '/', self::$Req->URI()));
						break;
					}
				}
		}
		if(is_null($app)) throw new KernelException(1, [PHP_SAPI, ($api=='cli') ? self::$Req->CMD() : self::$Req->URI()]);
		self::$Req->setAttribute('APP', $app);
		self::$Req->setAttribute('APP_NAMESPACE', $namespace);
		self::$Req->setAttribute('APP_DIR', self::info($namespace.'.class', self::INFO_PATH_DIR).'/');
		self::trace(LOG_DEBUG, T_INFO, $dispatcherID);
		Context::factory($namespace)->get($dispatcherID)->dispatch(self::$Req, self::$Res);
	}

	/**
	 * __autoload() implementation
	 * @param string $class class name
	 */
	static function autoload($class) {
		if(@file_exists($file = self::info($class, self::INFO_PATH).'.php')) {
			self::trace(LOG_DEBUG, T_AUTOLOAD, $class, null, __FUNCTION__);
			require($file);
			if(class_exists($class,0) || interface_exists($class,0) || trait_exists($class,0)) return;
		}
		trigger_error('FAILED loading '.$class, E_USER_ERROR);
	}

	/**
	 * ACL helper
	 * @return acl\ACL
	 */
	static function acl() {
		static $ACL;
		if(!isset($ACL) && !$ACL = self::cache('sys')->get('ACL')) {
			$ACL = new acl\ACL(self::$Sys->acl['config']['database'], self::$Sys->acl['config']['tables']);
			self::cache('sys')->set('ACL', $ACL);
		}
		return $ACL;
	}

	/**
	 * Cache helper
	 * @param string $id Cache ID, default "system"
	 * @return cache\CacheInterface
	 */
	static function cache($id='main') {
		static $_ = [];
		if(!isset($_[$id])) {
			$cnf = self::$Sys->cache[$id];
			$RefClass = new \ReflectionClass($cnf['class']);
			$params = ($cnf['params']) ? array_merge(['id'=>$id], $cnf['params']) : ['id'=>$id];
			$Cache = $RefClass->newInstanceArgs($params);
			$_[$id] = $Cache;
		}
		return $_[$id];
	}

	/**
	 * Parse class or namespace, returning: namespace, class name (without namespace), full path, directory, file
	 * @param string $path
	 * @param int|null $return
	 * @return array|string|false
	 */
	static function info($path, $return=null) {
		$path= str_replace('.', '\\', $path);
		if(false === $i = strrpos($path, '\\')) {
			$namespace = null;
			$class = $path;
		} else {
			$namespace = substr($path, 0 , $i);
			$class = substr($path, $i+1);
		}
		$realPath = null;
		foreach(self::$namespaces as $baseName => $baseDir) {
			if(0 === strpos($path, $baseName)) {
				$realPath = $baseDir.str_replace(['\\','_'], DIRECTORY_SEPARATOR, substr($namespace, strlen($baseName)).DIRECTORY_SEPARATOR.$class);
				break;
			}
		}
		switch ($return) {
			case self::INFO_NAMESPACE: return $namespace; break;
			case self::INFO_CLASS: return $class; break;
			case self::INFO_PATH: return $realPath; break;
			case self::INFO_PATH_DIR: return dirname($realPath); break;
			case self::INFO_PATH_FILE: return basename($realPath); break;
			default: return [$namespace, $class, dirname($realPath), basename($realPath)];
		}
	}

	/**
	 * System log helper
	 * @param string $message log message
	 * @param integer $level log level, one of the LOG_* constants
	 * @param string $facility log facility
	 */
	static function log($message, $level=LOG_INFO, $facility=null) {
		static $Logger;
		if(!isset($Logger)) $Logger = new log\Logger(self::$Sys->log);
		$Logger->log($message, $level, $facility);
	}

	/**
	 * PDO helper
	 * @param string $id database ID, default "master"
	 * @return db\PDO shared PDO instance
	 */
	static function pdo($id='master') {
		static $_ = [];
		if(!isset($_[$id])) {
			$cnf = self::$Sys->pdo[$id];
			self::trace(LOG_INFO, T_DB, sprintf('open [%s] %s', $id, $cnf['dns']), null, __METHOD__);
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
	static function trace($level=LOG_DEBUG, $type=T_INFO, $msg=null, $data=null, $function=null) {
		Tracer::trace($level, $type, $msg, $data, $function);
	}

	/**
	 * YAML parser utility, supporting PHAR & ENVIRONMENT switch
	 * @param string $file YAML file path
	 * @param string|null $section optional YAML section to be parsed
	 * @param array $callbacks content handlers for YAML nodes
	 * @return array
	 * @throws Exception
	 */
	static function yaml($file, $section=null, array $callbacks=[]) {
		$fileEnv = str_replace(['.yml','.yaml'], ['.'.ENVIRONMENT.'.yml', '.'.ENVIRONMENT.'.yaml'], $file);
		if(file_exists($fileEnv)) $file = $fileEnv;
		elseif(!file_exists($file)) throw new Exception(__FUNCTION__.' YAML not found: '.$file);
		if(strpos($file, 'phar://')!==false) {
			$tmp = tempnam(TMP_DIR, 'yaml-');
			file_put_contents($tmp, file_get_contents($file));
			$YAML = yaml_parse_file($tmp, 0, $n, $callbacks);
			unlink($tmp);
		} else $YAML = yaml_parse_file($file, 0, $n, $callbacks);
		return ($section) ? $YAML[$section] : $YAML;
	}
}
spl_autoload_register(__NAMESPACE__.'\sys::autoload');
