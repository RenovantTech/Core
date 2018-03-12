<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core;
use const metadigit\core\trace\{T_AUTOLOAD, T_DB, T_INFO};
use metadigit\core\acl\ACL,
	metadigit\core\auth\AUTH,
	metadigit\core\auth\AuthException,
	metadigit\core\console\CmdManager,
	metadigit\core\console\Event as ConsoleEvent,
	metadigit\core\container\Container,
	metadigit\core\container\ContainerException,
	metadigit\core\context\Context,
	metadigit\core\context\ContextException,
	metadigit\core\event\Event,
	metadigit\core\event\EventDispatcher,
	metadigit\core\event\EventDispatcherException,
	metadigit\core\http\Event as HttpEvent,
	metadigit\core\log\Logger;
/**
 * System Kernel
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class sys {

	const CACHE_FILE		= CACHE_DIR.'sys.php';
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
	/** System Cache
	 * @var \metadigit\core\cache\CacheInterface */
	static protected $Cache;
	/** System Container
	 * @var \metadigit\core\container\Container */
	static protected $Container;
	/** System Context
	 * @var \metadigit\core\context\Context */
	static protected $Context;
	/** System EventDispatcher
	 * @var \metadigit\core\event\EventDispatcher */
	static protected $EventDispatcher;
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
	/** trace store
	 * @var array */
	static protected $trace = [];
	/** trace current scope
	 * @var string */
	static protected $traceFn;
	/** trace level
	 * @var integer */
	static protected $traceLevel = LOG_DEBUG;

	/** HTTP/CLI apps routing
	 * @var array */
	protected $cnfApps = [];
	/** Cache configurations
	 * @var array */
	protected $cnfCache = [];
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
	 * @throws ContextException
	 * @throws EventDispatcherException
	 * @throws ContainerException
	 * @throws util\yaml\YamlException
	 * @throws \ReflectionException
	 */
	static function init() {
		self::$traceFn = __METHOD__;
		self::trace(LOG_DEBUG, T_INFO);
		set_exception_handler(__NAMESPACE__.'\trace\Tracer::onException');
		set_error_handler(__NAMESPACE__.'\trace\Tracer::onError');
		register_shutdown_function(__CLASS__.'::shutdown');

		// ENVIRONMENT FIX
		if(isset($_SERVER['REDIRECT_PORT'])) $_SERVER['SERVER_PORT'] = $_SERVER['REDIRECT_PORT'];

		// environment settings
		ignore_user_abort(1);
		ini_set('upload_tmp_dir', TMP_DIR);

		if(file_exists(self::CACHE_FILE)) include self::CACHE_FILE;
		else SysBoot::boot();

		// settings
		date_default_timezone_set(self::$Sys->cnfSettings['timeZone']);
		setlocale(LC_ALL, self::$Sys->cnfSettings['locale']);
		ini_set('default_charset', self::$Sys->cnfSettings['charset']);
		// constants
		foreach(self::$Sys->cnfConstants as $k => $v) define($k, $v);

		// TRACE service
		self::$traceLevel = self::$Sys->cnfTrace['level'];

		// initialize
		self::$Container = new Container;
		self::$EventDispatcher = new EventDispatcher;
		self::$Context = new Context(self::$Container, self::$EventDispatcher);
		self::$Context->init('sys');
		self::$EventDispatcher->trigger(self::EVENT_INIT);
	}

	/**
	 * @throws ContextException
	 * @throws EventDispatcherException
	 * @throws \ReflectionException
	 */
	static function shutdown() {
		ini_set('precision', 16);
		defined(__NAMESPACE__.'\trace\TRACE_END_TIME') or define(__NAMESPACE__.'\trace\TRACE_END_TIME',microtime(1));
		ini_restore('precision');
		self::$traceFn = __METHOD__;
		register_shutdown_function(__NAMESPACE__.'\trace\Tracer::shutdown');
		self::$EventDispatcher->trigger(self::EVENT_SHUTDOWN);
		if(PHP_SAPI != 'cli') session_write_close();
		// LOG service
		if(!empty(self::$log)) {
			$Logger = new log\Logger;
			foreach(self::$Sys->cnfLog as $cnf) {
				$Writer = new $cnf['class']($cnf['param1'], $cnf['param2']);
				$Logger->addWriter($Writer, constant($cnf['level']), $cnf['facility']);
			}
		}
	}

	/**
	 * Dispatch HTTP/CLI request
	 * @param string $api PHP_SAPI
	 * @throws SysException
	 * @throws ContextException
	 * @throws EventDispatcherException
	 * @throws \ReflectionException
	 */
	static function dispatch($api=PHP_SAPI) {
		($api=='cli') ? self::dispatchCLI() : self::dispatchHTTP();
	}

	/**
	 * @throws ContextException
	 * @throws EventDispatcherException
	 * @throws SysException
	 * @throws \ReflectionException
	 */
	static protected function dispatchCLI() {
		self::trace(LOG_DEBUG, T_INFO, null, null, __METHOD__);
		self::$Req = new console\Request;
		self::$Res = new console\Response;
		try {
			$pidLock = RUN_DIR.str_replace(' ', '-', self::$Req->CMD()).'.pid';
			file_put_contents($pidLock, getmypid());
			$app = $dispatcherID = $namespace = null;
			foreach(self::$Sys->cnfApps['CLI'] as $id => $namespace) {
				if(self::$Req->CMD(0) == $id) {
					$app = $id;
					$dispatcherID = $namespace.'.Dispatcher';
					self::$Req->setAttribute('APP_URI', trim(strstr(self::$Req->CMD(),' ')));
					break;
				};
			}
			if(is_null($app)) throw new SysException(1, [PHP_SAPI, self::$Req->CMD()]);
			self::$Req->setAttribute('APP', $app);
			self::$Req->setAttribute('APP_NAMESPACE', $namespace);
			self::$Req->setAttribute('APP_DIR', self::info($namespace.'.class', self::INFO_PATH_DIR).'/');
			$HttpEvent = new ConsoleEvent(self::$Req, self::$Res);
			self::$EventDispatcher->trigger(ConsoleEvent::EVENT_INIT, $HttpEvent);
			self::$Context->get($dispatcherID)->dispatch(self::$Req, self::$Res);
		} finally {
			unlink($pidLock);
		}
	}

	/**
	 * @throws ContextException
	 * @throws EventDispatcherException
	 * @throws SysException
	 * @throws \ReflectionException
	 */
	static protected function dispatchHTTP() {
		self::trace(LOG_DEBUG, T_INFO, null, null, __METHOD__);
		self::$Req = new http\Request;
		self::$Res = new http\Response;
		$app = $dispatcherID = $namespace = null;
		foreach(self::$Sys->cnfApps['HTTP'] as $id => $conf) {
			if(strpos($_SERVER['REQUEST_URI'], $conf['url']) === 0 &&
				(!isset($conf['domain']) || $_SERVER['SERVER_ADDR']==$conf['domain']) &&
				(!isset($conf['port']) || $_SERVER['SERVER_PORT']==$conf['port']))
			{
				$app = $id;
				$namespace = $conf['namespace'];
				$dispatcherID = $namespace.'.Dispatcher';
				self::$Req->setAttribute('APP_URI', '/'.substr(self::$Req->URI(), strlen($conf['url'])));
				break;
			}
		}
		if(is_null($app)) throw new SysException(1, [strtoupper(PHP_SAPI), $_SERVER['SERVER_ADDR'], $_SERVER['SERVER_PORT'], self::$Req->URI()]);
		self::$Req->setAttribute('APP', $app);
		self::$Req->setAttribute('APP_NAMESPACE', $namespace);
		self::$Req->setAttribute('APP_DIR', self::info($namespace.'.class', self::INFO_PATH_DIR).'/');
		$HttpEvent = new HttpEvent(self::$Req, self::$Res);
		try {
			self::$EventDispatcher->trigger(HttpEvent::EVENT_INIT, $HttpEvent);
			self::$Context->get($dispatcherID)->dispatch(self::$Req, self::$Res);
		} catch (AuthException $Ex) {
			http_response_code(401);
			$HttpEvent->setException($Ex);
			sys::event(HttpEvent::EVENT_EXCEPTION, $HttpEvent);
		}
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
	 * @return ACL
	 * @throws ContextException
	 * @throws EventDispatcherException
	 */
	static function acl() {
		static $ACL;
		/** @var ACL $ACL */
		if(!$ACL) $ACL = self::$Context->get('sys.ACL', ACL::class);
		return $ACL;
	}

	/**
	 * AUTH helper
	 * @return AUTH
	 * @throws ContextException
	 * @throws EventDispatcherException
	 */
	static function auth() {
		static $AUTH;
		/** @var AUTH $AUTH */
		if(!$AUTH) $AUTH = self::$Context->get('sys.AUTH', AUTH::class);
		return $AUTH;
	}

	/**
	 * Cache helper
	 * @param string $id Cache ID, default "system"
	 * @return cache\CacheInterface
	 */
	static function cache($id='main') {
		static $c = [];
		if($id=='sys') return self::$Cache;
		if(!isset($c[$id]) && !$c[$id] = self::cache('sys')->get($_ = 'sys.cache.'.strtoupper($id))) {
			$cnf = self::$Sys->cnfCache[$id];
			$c[$id] = self::$Container->build($_, $cnf['class'], $cnf['constructor'], $cnf['properties']);
			self::cache('sys')->set($_, $c[$id]);
		}
		return $c[$id];
	}

	/**
	 * CmdManager helper
	 * @return console\CmdManager
	 */
	static function cmd() {
		static $CmdManager;
		if(!isset($CmdManager) && !$CmdManager = self::cache('sys')->get('sys.CmdManager')) {
			$CmdManager = self::$Container->build('sys.CmdManager', CmdManager::class);
			self::cache('sys')->set('sys.CmdManager', $CmdManager);
		}
		return $CmdManager;
	}

	/**
	 * Context helper
	 * @return Context
	 */
	static function context(): Context {
		return self::$Context;
	}

	/**
	 * EventDispatcher helper
	 * @param string $eventName the name of the event
	 * @param Event|array|null $EventOrParams custom Event object or params array
	 * @return Event the Event object
	 * @throws ContextException
	 * @throws EventDispatcherException
	 * @throws \ReflectionException
	 */
	static function event($eventName, $EventOrParams=null): Event {
		return self::$EventDispatcher->trigger($eventName, $EventOrParams);
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
	 * System log helper
	 * @param string $message log message
	 * @param integer $level log level, one of the LOG_* constants, default: LOG_INFO
	 * @param string $facility optional log facility, default NULL
	 */
	static function log($message, $level=LOG_INFO, $facility=null) {
		self::trace(LOG_DEBUG, T_INFO, sprintf('[%s] %s: %s', Logger::LABELS[$level], $facility, $message), null, __METHOD__);
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
}
spl_autoload_register(__NAMESPACE__.'\sys::autoload');
