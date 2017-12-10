<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core;
use const metadigit\core\trace\{T_AUTOLOAD, T_DB, T_EVENT, T_INFO};
use metadigit\core\context\Context,
	metadigit\core\event\Event,
	metadigit\core\log\Logger;
/**
 * System Kernel
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class sys {

	const CACHE_FILE		= CACHE_DIR.'sys';
	const EVENT_INIT		= 'sys:init';
	const EVENT_SHUTDOWN	= 'sys:shutdown';
	const INFO_NAMESPACE	= 1;
	const INFO_CLASS		= 2;
	const INFO_PATH			= 3;
	const INFO_PATH_DIR		= 4;
	const INFO_PATH_FILE	= 5;
	/** Namespace definitions, used by __autoload()
	 * @var array */
	static protected $namespaces = [
		__NAMESPACE__ => __DIR__
	];
	/** Events listeners (callbacks)
	 * @var array */
	static protected $listeners = [];
	/** Logger
	 * @var \metadigit\core\log\Logger */
	static protected $Logger;
	/** Log buffer
	 * @var array */
	static protected $log = [];
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
	/** trace store
	 * @var array */
	static protected $trace = [];
	/** trace current scope
	 * @var string */
	static protected $traceFn;
	/** trace level
	 * @var integer */
	static protected $traceLevel = LOG_DEBUG;

	/** ACL configurations
	 * @var array */
	protected $cnfAcl = [
		'routes' => false,
		'objects' => false,
		'orm' => false,
		'config' => [
			'database' => 'master',
			'tables' => null
		]
	];
	/** AUTH configurations
	 * @var array */
	protected $cnfAuth = [
		'enableJWT' => false,
		'enableSESSION' => true
	];
	/** HTTP/CLI apps routing
	 * @var array */
	protected $cnfApps = [];
	/** Cache configurations
	 * @var array */
	protected $cnfCache = [
		'sys' => [
			'class' => 'metadigit\core\cache\SqliteCache',
			'params' => ['sys-cache', 'cache', true]
		]
	];
	/** Constants
	 * @var array */
	protected $cnfConstants = [];
	/** LogWriters configurations
	 * @var array */
	protected $cnfLog = [];
	/** Database PDO configurations
	 * @var array */
	protected $cnfPdo = [
		'sys-cache' => [ 'dns' => 'sqlite:'.CACHE_DIR.'sys-cache.sqlite' ],
		'sys-trace' => [ 'dns' => 'sqlite:'.DATA_DIR.'sys-trace.sqlite' ]
	];
	/** system settings
	 * @var array */
	protected $cnfSettings = [
		'charset'		=> 'UTF-8',
		'locale'		=> 'en_US.UTF-8',
		'timeZone'		=> 'UTC'
	];
	/** trace settings
	 * @var array */
	protected $cnfTrace = [
		'level'			=> LOG_DEBUG,
		'storeFn'		=> null
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
		self::$traceFn = __METHOD__;
		self::trace(LOG_DEBUG, T_INFO);
		set_exception_handler(__NAMESPACE__.'\trace\Tracer::onException');
		set_error_handler(__NAMESPACE__.'\trace\Tracer::onError');
		register_shutdown_function(function () {
			ini_set('precision', 16);
			defined(__NAMESPACE__.'\trace\TRACE_END_TIME') or define(__NAMESPACE__.'\trace\TRACE_END_TIME',microtime(1));
			ini_restore('precision');
			self::trace(LOG_DEBUG, T_INFO, null, null, __CLASS__.'::shutdown');
			register_shutdown_function(__NAMESPACE__.'\trace\Tracer::shutdown');
			//self::$SystemContext->trigger(self::EVENT_SHUTDOWN);
			if(PHP_SAPI != 'cli') session_write_close();
		});

		// ENVIRONMENT FIX
		if(isset($_SERVER['REDIRECT_PORT'])) $_SERVER['SERVER_PORT'] = $_SERVER['REDIRECT_PORT'];

		// environment settings
		ignore_user_abort(1);
		ini_set('upload_tmp_dir', TMP_DIR);

		$Sys = $namespaces = null;
		if(file_exists(self::CACHE_FILE)) include self::CACHE_FILE;
		if(!isset($Sys)) list($Sys, $namespaces) = sysBoot::boot();
		self::$namespaces = $namespaces;
		/** @var \metadigit\core\sys Sys */
		self::$Sys = $Sys;

		// settings
		date_default_timezone_set($Sys->cnfSettings['timeZone']);
		setlocale(LC_ALL, $Sys->cnfSettings['locale']);
		ini_set('default_charset', $Sys->cnfSettings['charset']);
		// constants
		foreach($Sys->cnfConstants as $k => $v) define($k, $v);
		// ACL service
		define(__NAMESPACE__.'\ACL_ROUTES', (boolean) $Sys->cnfAcl['routes']);
		define(__NAMESPACE__.'\ACL_OBJECTS', (boolean) $Sys->cnfAcl['objects']);
		define(__NAMESPACE__.'\ACL_ORM', (boolean) $Sys->cnfAcl['orm']);
		// LOG service
		self::$Logger = new log\Logger;
		foreach($Sys->cnfLog as $cnf) {
			$Writer = new $cnf['class']($cnf['param1'], $cnf['param2']);
			self::$Logger->addWriter($Writer, constant($cnf['level']), $cnf['facility']);
		}
		// TRACE service
		self::$traceLevel = $Sys->cnfTrace['level'];

		// initialize
		self::cache('sys');
		if(ACL_ROUTES || ACL_OBJECTS || ACL_ORM) self::acl();
//		self::$SystemContext = Context::factory('system');
//		self::$SystemContext->trigger(self::EVENT_INIT);
	}

	/**
	 * Dispatch HTTP/CLI request
	 * @param string $api PHP_SAPI
	 * @throws SysException
	 */
	static function dispatch($api=PHP_SAPI) {
		self::$traceFn = __METHOD__;
		self::$Req = ($api=='cli') ? new cli\Request : new http\Request;
		self::$Res = ($api=='cli') ? new cli\Response : new http\Response;
		$app = $dispatcherID = $namespace = null;
		switch($api) {
			case 'cli':
				foreach(self::$Sys->cnfApps['CLI'] as $id => $namespace) {
					if(self::$Req->CMD(0) == $id) {
						$app = $id;
						$dispatcherID = $namespace.'.Dispatcher';
						self::$Req->setAttribute('APP_URI', trim(strstr(self::$Req->CMD(),' ')));
						break;
					};
				}
				break;
			default:
				foreach(self::$Sys->cnfApps['HTTP'] as $id => $conf) {
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
		if(is_null($app)) throw new SysException(1, [PHP_SAPI, ($api=='cli') ? self::$Req->CMD() : self::$Req->URI()]);
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
			self::trace(LOG_DEBUG, T_AUTOLOAD, $class, null, __METHOD__);
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
			$ACL = new acl\ACL(self::$Sys->cnfAcl['config']['database'], self::$Sys->cnfAcl['config']['tables']);
			self::cache('sys')->set('ACL', $ACL);
		}
		return $ACL;
	}

	/**
	 * AUTH helper
	 * @return auth\AUTH
	 */
	static function auth() {
		static $AUTH;
		if(!isset($AUTH) && !$AUTH = self::cache('sys')->get('sys.AUTH')) {
 			$AUTH = new auth\AUTH(self::$Sys->cnfAuth['enableJWT'], self::$Sys->cnfAuth['enableSESSION']);
			self::cache('sys')->set('sys.AUTH', $AUTH);
		}
		return $AUTH;
	}

	/**
	 * Cache helper
	 * @param string $id Cache ID, default "system"
	 * @return cache\CacheInterface
	 */
	static function cache($id='main') {
		static $_ = [];
		if(!isset($_[$id])) {
			$cnf = self::$Sys->cnfCache[$id];
			$RefClass = new \ReflectionClass($cnf['class']);
			$params = ($cnf['params']) ? array_merge(['id'=>$id], $cnf['params']) : ['id'=>$id];
			$Cache = $RefClass->newInstanceArgs($params);
			$_[$id] = $Cache;
		}
		return $_[$id];
	}

	/**
	 * Trigger an Event, calling attached listeners
	 * @param string $eventName	the name of the event
	 * @param \metadigit\core\event\Event|array|null $EventOrParams custom Event object or params array
	 * @return \metadigit\core\event\Event the Event object
	 */
	static function event($eventName, $EventOrParams=null): Event {
		sys::trace(LOG_DEBUG, T_EVENT, strtoupper($eventName));
		$Event = (is_object($EventOrParams)) ? $EventOrParams : new Event($EventOrParams);
		if(!isset(self::$listeners[$eventName])) return $Event;
		foreach(self::$listeners[$eventName] as $listeners) {
			foreach($listeners as $callback) {
				if(is_string($callback) && strpos($callback,'->')>0) {
					list($objID, $method) = explode('->', $callback);
					Context::factory(substr($objID, 0, strrpos($objID, '.')));
					$callback = [new CoreProxy($objID), $method];
				}
				call_user_func($callback, $Event);
				if($Event->isPropagationStopped()) break;
			}
		}
		return $Event;
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
//				$realPath = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $realPath);
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
	 * Add an Event listener on the specified event
	 * @param string   $eventName the name of the event to listen for
	 * @param callable $callback  the callback function to be invoked
	 * @param int      $priority  trigger precedence on the listeners chain (higher values execute earliest)
	 * @throws \Exception
	 */
	static function listen($eventName, $callback, $priority=1) {
		self::$listeners[$eventName][(int)$priority][] = $callback;
		krsort(self::$listeners[$eventName], SORT_NUMERIC);
	}

	/**
	 * System log helper
	 * @param string $message log message
	 * @param integer $level log level, one of the LOG_* constants, default: LOG_INFO
	 * @param string $facility optional log facility, default NULL
	 */
	static function log($message, $level=LOG_INFO, $facility=null) {
		sys::trace(LOG_DEBUG, T_INFO, sprintf('[%s] %s: %s', Logger::LABELS[$level], $facility, $message), null, __METHOD__);
		self::$log[] = [$message, $level, $facility, time()];
	}

	/**
	 * PDO helper
	 * @param string $id database ID, default "master"
	 * @return db\PDO shared PDO instance
	 * @throws \PDOException
	 */
	static function pdo($id='master') {
		static $_ = [];
		if(!isset($_[$id])) {
			$traceFn = self::traceFn(__METHOD__);
			$cnf = self::$Sys->cnfPdo[$id];
			self::trace(LOG_INFO, T_DB, sprintf('open [%s] %s', $id, $cnf['dns']), null, __METHOD__);
			$pdo = @new db\PDO($cnf['dns'], $cnf['user'], $cnf['pwd'], $cnf['options'], $id);
			$_[$id] = $pdo;
			self::traceFn($traceFn);
		}
		return $_[$id];
	}

	/**
	 * Trace helper
	 * @param integer $level trace level, use a LOG_* constant value
	 * @param integer $type trace type, use a T_* constant value
	 * @param string $msg the trace message
	 * @param mixed $data the trace data
	 * @param string $function the tracing object method / function
	 */
	static function trace($level=LOG_DEBUG, $type=T_INFO, $msg=null, $data=null, $function=null) {
		if($level > self::$traceLevel) return;
		$fn = str_replace('metadigit\core', '\\', $function?:self::$traceFn);
		self::$trace[] = [round(microtime(1)-$_SERVER['REQUEST_TIME_FLOAT'],5), memory_get_usage(), $level, $type, $fn, $msg, serialize($data)];
	}

	/**
	 * Setter/getter backtrace current scope
	 * @param string|null $fn
	 * @return string
	 */
	static function traceFn($fn=null) {
		$prev = self::$traceFn;
		if($fn) self::$traceFn = $fn;
		return $prev;
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