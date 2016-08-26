<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core;
use metadigit\core\context\Context;

// trace & Profiler
defined('TRACE')						or define('TRACE', false);
define('TRACE_ERROR',		0);
define('TRACE_DEFAULT',		1);
define('TRACE_AUTOLOADING',	2);
define('TRACE_DB',			3);
define('TRACE_DEPINJ',		4);
define('TRACE_CACHE',		5);
define('TRACE_EVENT',		6);
defined('PROFILER')						or define('PROFILER', false);
// system
define('EOL', "\r\n");
define('metadigit\core\VERSION',		'3.0.0');
defined('metadigit\core\BOOTSTRAP')		or die('BOOTSTRAP not defined!');
defined('metadigit\core\BASE_DIR')		or die('BASE_DIR not defined!');
define('metadigit\core\DIR', (\Phar::running()) ? \Phar::running() : __DIR__);
// environment
defined('metadigit\core\ENVIRONMENT')	or define('metadigit\core\ENVIRONMENT', 'PROD');

/**
 * Cache helper
 * @param string $id Cache ID, default "system"
 * @return cache\CacheInterface
 */
function cache($id='system') {
	return Kernel::cache($id);
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
 * Kernel
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Kernel {

	const CONFIG_FILE		= 'metadigit-core.yaml';
	const EVENT_INIT		= 'kernel:init';
	const EVENT_SHUTDOWN	= 'kernel:shutdown';

	/** HTTP/CLI apps routing
	 * @var array */
	static protected $apps;
	/** System Context
	 * @var \metadigit\core\context\Context */
	static protected $SystemContext;
	/** LogWriters configurations
	 * @var array */
	static protected $logConf;
	/** Php classes namespace definitions, used by __autoload()
	 * @var array */
	static protected $namespaces = [
		'metadigit\core' => DIR
	];
	/** Current HTTP/CLI Request
	 * @var object */
	static protected $Req;
	/** Current HTTP/CLI Response
	 * @var object */
	static protected $Res;
	/** system settings
	 * @var array */
	static private $settings = [
		'traceLevel'	=> LOG_DEBUG,
		'charset'		=> 'UTF-8',
		'locale'		=> 'en_US.UTF-8',
		'timeZone'		=> 'UTC'
	];

	/**
	 * Kernel bootstrap.
	 * It has the following functions:
	 * * set user defined constants;
	 * * set global php settings (TimeZone, charset);
	 * * initialize classes auto-loading;
	 * - register error & exception handlers.
	 * @param string $configFile configuration .ini path, relative to BASE_DIR
	 * @return void
	 */
	static function init($configFile=self::CONFIG_FILE) {
		TRACE and self::trace(LOG_DEBUG, TRACE_DEFAULT, null, null, __METHOD__);
		// ENVIRONMENT FIX
		if(isset($_SERVER['REDIRECT_PORT'])) $_SERVER['SERVER_PORT'] = $_SERVER['REDIRECT_PORT'];
		ignore_user_abort(1);
		ini_set('upload_tmp_dir', TMP_DIR);
		spl_autoload_register(__CLASS__.'::autoload');
		register_shutdown_function(__CLASS__.'::shutdown');

		if(!$config = yaml_parse_file(BASE_DIR.$configFile)) die('Invalid Core configuration file: '.$configFile);

		// settings
		self::$settings = array_replace(self::$settings, $config['settings']);
		date_default_timezone_set(self::$settings['timeZone']);
		setlocale(LC_ALL,self::$settings['locale']);
		ini_set('default_charset',self::$settings['charset']);
		self::$traceLevel = self::$settings['traceLevel'];
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
		// APPS HTTP/CLI
		self::$apps['HTTP'] = $config['apps'];
		self::$apps['CLI'] = $config['cli'];
		// constants
		foreach($config['constants'] as $k => $v) define($k, $v);
		// caches
		if(is_array($config['caches'])) self::$cacheConf = array_merge($config['caches'], self::$cacheConf);
		// databases
		if(is_array($config['databases'])) self::$dbConf = array_merge($config['databases'], self::$dbConf);
		// logs
		if(is_array($config['logs'])) self::$logConf = $config['logs'];

		// initialize
		if(!file_exists(DATA_DIR.'.metadigit-core')) KernelHelper::boot();
		self::cache('kernel');
		set_exception_handler(function() {
			call_user_func_array('metadigit\core\KernelDebugger::onException', func_get_args());
		});
		set_error_handler(function() {
			if(error_reporting()===0) return;
			call_user_func_array('metadigit\core\KernelDebugger::onError', func_get_args());
		});
		self::$SystemContext = Context::factory('system');
		self::$SystemContext->trigger(self::EVENT_INIT);
	}

	/**
	 * Dispatch HTTP/CLI request
	 * @param string $api PHP_SAPI
	 * @throws KernelException
	 */
	static function dispatch($api=PHP_SAPI) {
		self::$Req = ($api=='cli') ? new cli\Request : new http\Request;
		self::$Res = ($api=='cli') ? new cli\Response : new http\Response;
		$app = null;
		switch($api) {
			case 'cli':
				foreach(self::$apps['CLI'] as $id => $namespace) {
					if(self::$Req->CMD(0) == $id) {
						$app = $id;
						$dispatcherID = $namespace.'.Dispatcher';
						self::$Req->setAttribute('APP_URI', trim(strstr(self::$Req->CMD(),' ')));
						break;
					};
				}
				break;
			default:
				foreach(self::$apps['HTTP'] as $id => $conf) {
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
		$parse = self::parseClassName(str_replace('.','\\', $namespace.'.class'));
		self::$Req->setAttribute('APP_DIR', $parse[2].'/');
		TRACE and self::trace(LOG_DEBUG, TRACE_DEFAULT, $dispatcherID, null, __METHOD__);
		Context::factory($namespace)->get($dispatcherID)->dispatch(self::$Req, self::$Res);
	}

	/**
	 * Automatic shutdown handler
	 */
	static function shutdown() {
		self::$SystemContext->trigger(self::EVENT_SHUTDOWN);
		cache\SqliteCache::shutdown();
		$err = error_get_last();
		if(in_array($err['type'], [E_ERROR,E_CORE_ERROR,E_COMPILE_ERROR,])) {
			self::$traceError = KernelDebugger::E_ERROR;
			KernelDebugger::onError($err['type'], $err['message'], $err['file'], $err['line'], null, debug_backtrace(false));
			http_response_code(500);
		}
		if(PHP_SAPI != 'cli') session_write_close();
		self::logFlush();
	}

	// === CACHE ==================================================================================

	/** Cache instances
	 * @var array */
	static protected $cache;
	/** Cache configurations
	 * @var array */
	static protected $cacheConf = [
		'kernel' => [
			'class' => 'metadigit\core\cache\SqliteCache',
			'params' => ['kernel-cache', 'cache', true]
		]
	];

	/**
	 * Return shared Cache instance
	 * @param string $id Cache ID, default "system"
	 * @return \metadigit\core\cache\CacheInterface
	 */
	static function cache($id='system') {
		if(!isset(self::$cache[$id])) {
			$cnf = self::$cacheConf[$id];
			$ReflClass = new \ReflectionClass($cnf['class']);
			$params = ($cnf['params']) ? array_merge(['id'=>$id], $cnf['params']) : ['id'=>$id];
			$Cache = $ReflClass->newInstanceArgs($params);
			self::$cache[$id] = $Cache;
		}
		return self::$cache[$id];
	}

	// === DATABASES ==============================================================================

	/** PDO instances
	 * @var array */
	static protected $db;
	/** Database PDO configurations
	 * @var array */
	static protected $dbConf = [
		'kernel-cache' => [ 'dns' => 'sqlite:'.CACHE_DIR.'kernel-cache.sqlite' ],
		'kernel-trace' => [ 'dns' => 'sqlite:'.DATA_DIR.'kernel-trace.sqlite' ]
	];

	/**
	 * Return shared PDO instance
	 * @param string $id database ID, default "master"
	 * @return \PDO
	 */
	static function pdo($id='master') {
		if(!isset(self::$db[$id])) {
			$cnf = self::$dbConf[$id];
			TRACE and self::trace(LOG_DEBUG, TRACE_DB, sprintf('open db "%s": %s', $id, $cnf['dns']), null, __METHOD__);
			$pdo = new db\PDO($cnf['dns'], @$cnf['user'], @$cnf['pwd'], @$cnf['options']?:[]);
			self::$db[$id] = $pdo;
		}
		return self::$db[$id];
	}

	// === LOG SYSTEM =============================================================================

	/** log buffer
	 * @var array */
	static protected $log = [];

	/**
	 * Kernel log function.
	 * @param string $message log message
	 * @param integer $level log level, one of the LOG_* constants
	 * @param string $facility log facility
	 */
	static function log($message, $level=LOG_INFO, $facility=null) {
		TRACE and self::trace(LOG_DEBUG, 1, sprintf('[%s] %s: %s', log\Logger::LABELS[$level], $facility, $message), null, __METHOD__);
		self::$log[] = [$message, $level, $facility, time()];
	}

	static function logFlush() {
		static $Logger;
		if(!$Logger) {
			$Logger = new log\Logger();
			foreach(self::$logConf as $k => $cnf)
				$Logger->addWriter(new $cnf['class']($cnf['param1'], $cnf['param2']), constant($cnf['level']), $cnf['facility']);
		}
		if(!empty(self::$log)) {
			foreach(self::$log as $log)
				call_user_func_array([$Logger,'log'], $log);
		}
	}

	// === TRACE SYSTEM ===========================================================================

	/** backtrace store
	 * @var array */
	static protected $trace = [];
	/** backtrace Error level, incremented by errors & exceptions
	 * @var integer */
	static protected $traceError = 0;
	/** backtrace level
	 * @var integer */
	static protected $traceLevel = LOG_DEBUG;
	/** backtrace current scope
	 * @var string */
	static protected $traceFn;

	/**
	 * Kernel trace function.
	 * @param integer $level trace level, use a LOG_? constant value
	 * @param integer $type trace type, use a TRACE_? constant value
	 * @param string $msg the trace message
	 * @param mixed $data the trace data
	 * @param string $function the tracing object method / function
	 */
	static function trace($level=LOG_DEBUG, $type=TRACE_DEFAULT, $msg=null, $data=null, $function=null) {
		if($level > self::$traceLevel) return;
		$fn = str_replace('metadigit', '\\', $function?:self::$traceFn);
		self::$trace[] = [round(microtime(1)-$_SERVER['REQUEST_TIME_FLOAT'],5), memory_get_usage(), $level, $type, $fn, $msg, print_r($data,true)];
	}

	/**
	 * Setter/getter backtrace current scope
	 * @param string|null $fn
	 * @return string
	 */
	static function traceFn($fn=null) {
		if($fn) self::$traceFn = $fn;
		return self::$traceFn;
	}

	// === AUTO-LOADING SYSTEM ====================================================================

	/**
	 * __autoload() implementation
	 * @param string $class class name
	 * @return void
	 */
	static function autoload($class) {
		list($namespace, $className, $dir, $file) = self::parseClassName($class);
		if(@file_exists($path = $dir.'/all.cache.inc')) {
			TRACE and self::trace(LOG_DEBUG, TRACE_AUTOLOADING, $namespace.'\*', null, __METHOD__);
			require_once($path);
			if(class_exists($class,0) || interface_exists($class,0) || trait_exists($class,0)) return;
		}
		if(@file_exists($file = $dir.'/'.$file.'.php')) {
			TRACE and self::trace(LOG_DEBUG, TRACE_AUTOLOADING, $class, null, __METHOD__);
			require($file);
			if(class_exists($class,0) || interface_exists($class,0) || trait_exists($class,0)) return;
		}
		TRACE and self::trace(LOG_ERR, TRACE_ERROR, 'FAILED loading '.$class, null, __METHOD__);
		self::$traceError = 3;
		self::log(sprintf('ERROR loading class %s',$class ), LOG_ERR, 'kernel');
	}

	/**
	 *
	 * Parse class name, returning:
	 * - namespace
	 * - class name (without namespace)
	 * - directory
	 * - file
	 * @param string $class
	 * @return array
	 */
	static function parseClassName($class) {
		if(false === $i = strrpos($class, '\\')) {
			$namespace = null; $className = $class;
		} else {
			$namespace = substr($class, 0 , $i); $className = substr($class, $i+1);
		}
		foreach(self::$namespaces as $baseName => $baseDir) {
			if(0 === strpos($class, $baseName)) {
				$path = $baseDir.str_replace(['\\','_'], DIRECTORY_SEPARATOR, substr($namespace, strlen($baseName)).DIRECTORY_SEPARATOR.$className);
				return [$namespace, $className, dirname($path), basename($path)];
				break;
			}
		}
	}
}
