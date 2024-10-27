<?php
namespace renovant\core;

use renovant\core\cache\ArrayCache;
use renovant\core\auth\{Auth, AuthException};
use renovant\core\event\{EventDispatcher, EventDispatcherException};
use renovant\core\console\{CmdManager, Event as ConsoleEvent};
use renovant\core\context\{Context, ContextException};
use renovant\core\container\{Container, ContainerException};
use renovant\core\authz\Authz;
use renovant\core\db\PDO;
use renovant\core\http\Event as HttpEvent;
use renovant\core\log\Logger;
use renovant\core\queue\Queue;

use const renovant\core\cache\OBJ_ID_PREFIX;
use const renovant\core\trace\{T_AUTOLOAD, T_DB, T_INFO};

/**
 * System Kernel
 */
class sys {
	public const SYS_YAML_CACHE = CACHE_DIR . SYS_YAML . '.php';
	public const EVENT_INIT     = 'sys:init';
	public const EVENT_SHUTDOWN = 'sys:shutdown';
	public const INFO_NAMESPACE = 1;
	public const INFO_CLASS     = 2;
	public const INFO_PATH      = 3;
	public const INFO_PATH_DIR  = 4;
	public const INFO_PATH_FILE = 5;
	public const PDO_DEFAULT    = 'master';
	/** Namespace definitions, used by __autoload()
	 * @var array */
	protected static $namespaces = [
		__NAMESPACE__ => __DIR__
	];
	/** System Cache
	 * @var \renovant\core\cache\CacheInterface */
	protected static $Cache;
	/** System Container
	 * @var \renovant\core\container\Container */
	protected static $Container;
	/** System Context
	 * @var \renovant\core\context\Context */
	protected static $Context;
	/** System EventDispatcher
	 * @var \renovant\core\event\EventDispatcher */
	protected static $EventDispatcher;
	/** Logger
	 * @var \renovant\core\log\Logger */
	protected static $Logger;
	/** Log buffer
	 * @var array */
	protected static $log = [];
	/** PDO instances
	 * @var array */
	protected static $pdo = [];
	/** Current HTTP/CLI Request
	 * @var object */
	protected static $Req;
	/** Current HTTP/CLI Response
	 * @var object */
	protected static $Res;
	/** Current HTTP/CLI routes
	 * @var array */
	protected static $routes = [];
	/** Singleton instance
	 * @var sys */
	protected static $Sys;
	/** trace store
	 * @var array */
	protected static $trace = [];
	/** trace current scope
	 * @var string */
	protected static $traceFn;
	/** trace level
	 * @var integer */
	protected static $traceLevel = LOG_DEBUG;

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
	protected $cnfPdo = [];
	/** system settings
	 * @var array */
	protected $cnfSettings = [
		'charset'  => 'UTF-8',
		'locale'   => 'en_US.UTF-8',
		'timeZone' => 'UTC'
	];
	/** trace settings
	 * @var array */
	protected $cnfTrace = [
		'level'   => LOG_DEBUG,
		'storeFn' => null
	];
	/** sys services
	 * @var array */
	protected $cnfServices = [
		'auth'  => 'sys.AUTH',
		'authz' => 'sys.AUTHZ',
		'cmd'   => 'sys.CmdManager'
	];

	/**
	 * System Kernel bootstrap.
	 * It has the following functions:
	 * * set user defined constants;
	 * * set global php settings (TimeZone, charset);
	 * * initialize classes auto-loading;
	 * - register error & exception handlers.
	 * @param string $sys the system namespace to initialize
	 * @param string $app the APP namespace to initialize
	 * @throws ContainerException
	 * @throws ContextException
	 * @throws EventDispatcherException
	 * @throws \ReflectionException
	 * @throws util\yaml\YamlException
	 */
	public static function init(string $sys = 'sys', string $app = 'app') {
		self::$traceFn = __METHOD__;
		self::trace();
		set_exception_handler(__NAMESPACE__ . '\trace\Tracer::onException');
		set_error_handler(__NAMESPACE__ . '\trace\Tracer::onError');
		register_shutdown_function(__CLASS__ . '::shutdown');

		// ENVIRONMENT FIX
		if (isset($_SERVER['REDIRECT_PORT'])) {
			$_SERVER['SERVER_PORT'] = $_SERVER['REDIRECT_PORT'];
		}

		// environment settings
		ignore_user_abort(1);
		ini_set('upload_tmp_dir', TMP_DIR);

		if (file_exists(self::SYS_YAML_CACHE)) {
			include self::SYS_YAML_CACHE;
		} else {
			SysBoot::boot();
		}

		// settings
		date_default_timezone_set(self::$Sys->cnfSettings['timeZone']);
		setlocale(LC_ALL, self::$Sys->cnfSettings['locale']);
		ini_set('default_charset', self::$Sys->cnfSettings['charset']);
		// constants
		foreach (self::$Sys->cnfConstants as $k => $v) {
			define($k, $v);
		}

		// TRACE service
		self::$traceLevel = self::$Sys->cnfTrace['level'];

		// initialize
		self::$Container       = new Container();
		self::$EventDispatcher = new EventDispatcher();
		self::$Context         = new Context(self::$Container, self::$EventDispatcher);
		self::$Context->init($sys);
		self::$Context->init($app);
		self::$EventDispatcher->trigger(self::EVENT_INIT);
	}

	/**
	 * @throws ContextException
	 * @throws EventDispatcherException
	 * @throws \ReflectionException
	 */
	public static function shutdown() {
		ini_set('precision', 16);
		defined(__NAMESPACE__ . '\trace\TRACE_END_TIME') or define(__NAMESPACE__ . '\trace\TRACE_END_TIME', microtime(1));
		ini_restore('precision');
		self::$traceFn = __METHOD__;
		self::trace();
		foreach (self::$pdo as $PDO) {
			if ($PDO->inTransaction()) {
				$PDO->rollBack();
			}
		}
		register_shutdown_function(__NAMESPACE__ . '\trace\Tracer::shutdown');
		if (self::$EventDispatcher) {
			self::$EventDispatcher->trigger(self::EVENT_SHUTDOWN);
		}
		if (PHP_SAPI != 'cli') {
			session_write_close();
		}
		// LOG service
		if (!empty(self::$log)) {
			$Logger = new log\Logger();
			foreach (self::$Sys->cnfLog as $cnf) {
				$Writer = new $cnf['class']($cnf['param1'], $cnf['param2']);
				$Logger->addWriter($Writer, constant($cnf['level']), $cnf['facility']);
			}
		}
	}

	/**
	 * @param array $routes CLI APP modules routing
	 * @throws ContextException
	 * @throws EventDispatcherException
	 * @throws SysException
	 * @throws \ReflectionException
	 */
	public static function dispatchCLI(array $routes) {
		self::trace(LOG_DEBUG, T_INFO, null, null, __METHOD__);
		self::$Req    = new console\Request();
		self::$Res    = new console\Response();
		self::$routes = $routes;
		$app          = $dispatcherID = $namespace = null;
		foreach ($routes as $app => $conf) {
			if (self::$Req->CMD(0) == $conf['cmd']) {
				$namespace    = $conf['namespace'];
				$dispatcherID = $namespace . '.Dispatcher';
				self::$Req->setAttribute('APP_MOD_URI', trim(strstr(self::$Req->CMD(), ' ')));
				break;
			}
		}
		if (is_null($app)) {
			throw new SysException(1, [PHP_SAPI, self::$Req->CMD()]);
		}
		self::$Req->setAttribute('APP', $app);
		self::$Req->setAttribute('APP_MOD_NAMESPACE', $namespace);
		self::$Req->setAttribute('APP_MOD_DIR', self::info($namespace . '.class', self::INFO_PATH_DIR) . '/');
		$HttpEvent = new ConsoleEvent(self::$Req, self::$Res);
		self::$EventDispatcher->trigger(ConsoleEvent::EVENT_INIT, $HttpEvent);
		self::$Context->get($dispatcherID)->dispatch(self::$Req, self::$Res);
	}

	/**
	 * @param array $routes HTTP APP modules routing
	 * @throws ContextException
	 * @throws EventDispatcherException
	 * @throws \ReflectionException
	 */
	public static function dispatchHTTP($app, array $routes) {
		self::trace(LOG_DEBUG, T_INFO, null, null, __METHOD__);
		self::$Req    = new http\Request();
		self::$Res    = new http\Response();
		self::$routes = $routes;
		$HttpEvent    = new HttpEvent(self::$Req, self::$Res);
		$module       = $dispatcherID = $namespace = null;
		foreach ($routes as $module => $conf) {
			if (strpos($_SERVER['REQUEST_URI'], $conf['url']) === 0 &&
				(!isset($conf['domain']) || $_SERVER['SERVER_ADDR'] == $conf['domain']) &&
				(!isset($conf['port']) || $_SERVER['SERVER_PORT'] == $conf['port'])) {
				$namespace    = $conf['namespace'];
				$dispatcherID = $namespace . '.Dispatcher';
				self::$Req->setAttribute('APP_MOD_URI', '/' . ltrim('/' . substr(self::$Req->URI(), strlen($conf['url'])), '/'));
				self::trace(LOG_DEBUG, T_INFO, 'matched URL: ' . $conf['url'] . ' => MODULE namespace: ' . $namespace, null, 'sys->dispatchHTTP');
				break;
			}
		}
		try {
			if (is_null($namespace)) {
				throw new SysException(1, [strtoupper(PHP_SAPI), $_SERVER['SERVER_ADDR'], $_SERVER['SERVER_PORT'], self::$Req->URI()]);
			}
			self::$Req->setAttribute('APP', $app);
			self::$Req->setAttribute('APP_MOD', $module);
			self::$Req->setAttribute('APP_MOD_NAMESPACE', $namespace);
			self::$Req->setAttribute('APP_MOD_DIR', self::info($namespace . '.class', self::INFO_PATH_DIR) . '/');
			self::$EventDispatcher->trigger(HttpEvent::EVENT_INIT, $HttpEvent);
			self::$Context->get($dispatcherID)->dispatch(self::$Req, self::$Res);
		} catch (AuthException $Ex) {
			http_response_code(401);
			$HttpEvent->setException($Ex);
			sys::event()->trigger(HttpEvent::EVENT_EXCEPTION, $HttpEvent);
		} catch (SysException $Ex) {
			http_response_code(404);
			$HttpEvent->setException($Ex);
			sys::event()->trigger(HttpEvent::EVENT_EXCEPTION, $HttpEvent);
		} catch (\Exception $Ex) {
			http_response_code(500);
			$HttpEvent->setException($Ex);
			sys::event()->trigger(HttpEvent::EVENT_EXCEPTION, $HttpEvent);
		}
	}

	/**
	 * __autoload() implementation
	 * @param string $class class name
	 */
	public static function autoload(string $class) {
		if (@file_exists($file = self::info($class, self::INFO_PATH) . '.php')) {
			self::trace(LOG_DEBUG, T_AUTOLOAD, $class, null, __METHOD__);
			require $file;
			if (in_array(\renovant\core\db\orm\EntityTrait::class, class_uses($class))) {
				call_user_func($class . '::metadata');
			}
			if (class_exists($class, 0) || interface_exists($class, 0) || trait_exists($class, 0)) {
				return;
			}
		}
		trigger_error('FAILED loading ' . $class, E_USER_ERROR);
	}

	/** AUTH helper */
	public static function auth(): Auth {
		return Auth::instance();
	}

	/** AUTHZ helper */
	public static function authz(): ?Authz {
		return Authz::instance();
	}

	/**
	 * Cache helper
	 * Will return an ArrayCache on failure
	 * @param string $id Cache ID, default "main"
	 * @return cache\CacheInterface
	 */
	public static function cache(string $id = 'main') {
		static $c = [];
		if ($id == SYS_CACHE) {
			return self::$Cache;
		}
		try {
			if (!isset($c[$id]) && !$c[$id] = self::cache(SYS_CACHE)->get($_ = OBJ_ID_PREFIX . strtoupper($id))) {
				$cnf    = self::$Sys->cnfCache[$id];
				$c[$id] = self::$Container->build($_, $cnf['class'], $cnf['constructor'], $cnf['properties']);
				self::cache(SYS_CACHE)->set($_, $c[$id]);
			}
			return $c[$id];
		} catch (\Exception $Ex) {
			return new ArrayCache();
		}
	}

	/**
	 * CmdManager helper
	 * @throws ContextException
	 * @throws EventDispatcherException
	 * @throws \ReflectionException
	 */
	public static function cmd(): CmdManager {
		static $CmdManager;
		if (!$CmdManager) {
			$CmdManager = self::$Context->get(self::$Sys->cnfServices['cmd'], CmdManager::class);
		}
		return $CmdManager;
	}

	/**
	 * Context helper
	 * @return Context
	 */
	public static function context(): Context {
		return self::$Context;
	}

	/**
	 * EventDispatcher helper
	 */
	public static function event(): EventDispatcher {
		return self::$EventDispatcher;
	}

	/**
	 * Parse class or namespace, returning: namespace, class name (without namespace), full path, directory, file
	 * @param string $path
	 * @param int|null $return
	 * @return array|string|false
	 */
	public static function info(string $path, $return = null) {
		$path = str_replace('.', '\\', $path);
		if (false === $i = strrpos($path, '\\')) {
			$namespace = '';
			$class     = $path;
		} else {
			$namespace = substr($path, 0, $i);
			$class     = substr($path, $i + 1);
		}
		$realPath = '';
		foreach (self::$namespaces as $baseName => $baseDir) {
			if (0 === strpos($path, $baseName)) {
				$realPath = $baseDir . str_replace(['\\', '_'], DIRECTORY_SEPARATOR, substr($namespace, strlen($baseName)) . DIRECTORY_SEPARATOR . $class);
				//				$realPath = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $realPath);
				break;
			}
		}
		switch ($return) {
			case self::INFO_NAMESPACE: return $namespace;
			case self::INFO_CLASS: return $class;
			case self::INFO_PATH: return $realPath;
			case self::INFO_PATH_DIR: return dirname($realPath);
			case self::INFO_PATH_FILE: return basename($realPath);
			default: return [$namespace, $class, dirname($realPath), basename($realPath)];
		}
	}

	/**
	 * System log helper
	 * @param string $message log message
	 * @param integer $level log level, one of the LOG_* constants, default: LOG_INFO
	 * @param null $facility optional log facility, default NULL
	 */
	public static function log(string $message, $level = LOG_INFO, $facility = null) {
		self::trace(LOG_DEBUG, T_INFO, sprintf('[%s] %s: %s', Logger::LABELS[$level], $facility, $message), null, __METHOD__);
		self::$log[] = [$message, $level, $facility, time()];
	}

	/**
	 * QUEUE helper
	 * @return Queue
	 * @throws ContextException|EventDispatcherException|\ReflectionException
	 */
	public static function queue() {
		static $Queue;
		/** @var Queue $Queue */
		if (!$Queue) {
			$Queue = self::$Context->get('sys.Queue', Queue::class);
		}
		return $Queue;
	}

	/**
	 * PDO helper
	 * @param string|null $id database ID, default "master"
	 */
	public static function pdo(?string $id = null): PDO {
		if (is_null($id)) {
			$id = self::PDO_DEFAULT;
		}
		if (!isset(self::$pdo[$id])) {
			$traceFn = self::traceFn(__METHOD__);
			$cnf     = self::$Sys->cnfPdo[$id];
			self::trace(LOG_INFO, T_DB, sprintf('open [%s] %s', $id, $cnf['dns']), null, __METHOD__);
			$pdo            = @new PDO((string)$cnf['dns'], $cnf['user'], $cnf['pwd'], $cnf['options'], $id);
			self::$pdo[$id] = $pdo;
			self::traceFn($traceFn);
		}
		return self::$pdo[$id];
	}

	/**
	 * Trace helper
	 * @param integer $level trace level, use a LOG_* constant value
	 * @param integer $type trace type, use a T_* constant value
	 * @param string|null $msg the trace message
	 * @param mixed $data the trace data
	 * @param string|null $function the tracing object method / function
	 */
	public static function trace(int $level = LOG_DEBUG, int $type = T_INFO, ?string $msg = null, $data = null, ?string $function = null) {
		if ($level > self::$traceLevel) {
			return;
		}
		$fn            = str_replace('renovant\core', '\\', $function ?: self::$traceFn);
		self::$trace[] = [round(microtime(1) - $_SERVER['REQUEST_TIME_FLOAT'], 5), memory_get_usage(), $level, $type, $fn, $msg, serialize($data)];
	}

	/**
	 * Setter/getter backtrace current scope
	 * @param string|null $fn
	 * @return string
	 */
	public static function traceFn(?string $fn = null) {
		$prev = self::$traceFn;
		if ($fn) {
			self::$traceFn = $fn;
		}
		return $prev;
	}
}
spl_autoload_register(__NAMESPACE__ . '\sys::autoload');
