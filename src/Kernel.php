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
define('metadigit\core\VERSION',		'2.0.1');
defined('metadigit\core\BOOTSTRAP')		or die('BOOTSTRAP not defined!');
defined('metadigit\core\BASE_DIR')		or die('BASE_DIR not defined!');
define('metadigit\core\DIR', (\Phar::running()) ? \Phar::running() : __DIR__);
// environment
defined('metadigit\core\ENVIRONMENT')	or define('metadigit\core\ENVIRONMENT', 'PROD');

/**
 * Kernel
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Kernel {

	const CONFIG_FILE	= 'metadigit-core.ini';
	const EVENT_INIT		= 'kernel:init';
	const EVENT_SHUTDOWN	= 'kernel:shutdown';

	/** HTTP/CLI apps routing
	 * @var array */
	static protected $apps;
	/** Cache instance
	 * @var \metadigit\core\cache\CacheInterface */
	static protected $Cache;
	/** Database PDO configurations
	 * @var array */
	static protected $dbConf;
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
	/** PDO instances
	 * @var array */
	static protected $_pdo;
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
	 * * initialize classes autoloading;
	 * - register error & exception handlers.
	 * @param string $configFile configuration .ini path, relative to BASE_DIR
	 * @return void
	 */
	static function init($configFile=self::CONFIG_FILE) {
		TRACE and self::trace(LOG_DEBUG, 1, __METHOD__);
		// ENVIRONMENT FIX
		if(PHP_SAPI!='cli' && strpos($_SERVER['SERVER_SOFTWARE'],'lighttpd')!==false) {
			$_SERVER['SERVER_NAME']=strstr($_SERVER['SERVER_NAME'].':',':',true);
			$_SERVER['QUERY_STRING']=parse_url($_SERVER['REQUEST_URI'],PHP_URL_QUERY);
			parse_str($_SERVER['QUERY_STRING'],$_GET);
			$_REQUEST=array_merge($_REQUEST,$_GET);
		}
		if(isset($_SERVER['REDIRECT_PORT'])) $_SERVER['SERVER_PORT'] = $_SERVER['REDIRECT_PORT'];
		ignore_user_abort(1);
		//ini_set('display_errors',1); // @TODO boh! probably useless!!!
		ini_set('upload_tmp_dir', TMP_DIR);
		spl_autoload_register(__CLASS__.'::autoload');
		register_shutdown_function(__CLASS__.'::shutdown');
		if(!$config = parse_ini_file(BASE_DIR.$configFile, true)) die('Invalid Core configuration file: '.$configFile);
		foreach($config as $section => $data) {
			switch($section) {
				case 'settings':
					self::$settings = array_replace(self::$settings, $data);
					date_default_timezone_set(self::$settings['timeZone']);
					setlocale(LC_ALL,self::$settings['locale']);
					ini_set('default_charset',self::$settings['charset']);
					self::$traceLevel = self::$settings['traceLevel'];
					break;
				case 'namespaces':
					foreach($data as $k => $dir) {
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
					break;
				case 'apps-http':
					foreach($data as $k => $v) {
						list($httpPort, $baseUrl, $namespace) = explode('|',$v);
						self::$apps['HTTP'][$k]['httpPort']		= (int) $httpPort;
						self::$apps['HTTP'][$k]['baseUrl']		= $baseUrl;
						self::$apps['HTTP'][$k]['namespace']	= $namespace;
					}
					break;
				case 'apps-cli':
					foreach($data as $k => $v) self::$apps['CLI'][$k] = $v;
					break;
				case 'constants':
					foreach($data as $k => $v) define($k, $v);
					break;
				case 'databases':
					self::$dbConf = array_merge([
						'kernel-cache'=>'sqlite:'.CACHE_DIR.'kernel-cache.sqlite|null|null',
						'kernel-trace'=>'sqlite:'.DATA_DIR.'kernel-trace.sqlite|null|null'
					], $data);
					break;
				case 'logs':
					self::$logConf = $data;
					break;
			}
		}
		if(!file_exists(DATA_DIR.'.metadigit-core')) KernelHelper::boot();
		self::$Cache = new cache\SqliteCache('kernel-cache', 'cache', true);
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
		TRACE and self::trace(LOG_DEBUG, 1, __METHOD__, $dispatcherID);
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

	// === DATABASES ==============================================================================

	/**
	 * Return shared PDO instance
	 * @param string $id database ID, default "master"
	 * @param boolean $raw TRUE for a native Php PDO, FALSE for a metadigit\core\db\PDO
	 * @return \PDO
	 */
	static function pdo($id='master', $raw=true) {
		if(!isset(self::$_pdo[$id])) {
			list($dns,$user,$pw) = @explode('|',self::$dbConf[$id]);
			TRACE and self::trace(LOG_DEBUG, TRACE_DB, __METHOD__, sprintf('open db "%s": %s', $id, $dns));
			$pdo = new \PDO($dns,$user,$pw);
			$pdo->setAttribute(\PDO::ATTR_ERRMODE,\PDO::ERRMODE_EXCEPTION);
			if('sqlite'==$pdo->getAttribute(\PDO::ATTR_DRIVER_NAME)) {
				if(file_exists(TMP_DIR.$id.'.vacuum')) unlink(TMP_DIR.$id.'.vacuum') && $pdo->exec('VACUUM');
				$pdo->exec('PRAGMA journal_mode = WAL');
				$pdo->exec('PRAGMA temp_store = MEMORY');
				$pdo->exec('PRAGMA synchronous = OFF');
				$pdo->exec('PRAGMA foreign_keys = ON');
			}
			self::$_pdo[$id] = $pdo;
		}
		return self::$_pdo[$id];
	}

	// === CACHE ==================================================================================

	/**
	 * @return \metadigit\core\cache\CacheInterface
	 */
	static function getCache() {
		return self::$Cache;
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
		TRACE and self::trace(LOG_DEBUG, 1, __METHOD__, sprintf('[%s] %s: %s', log\Logger::$labels[$level], $facility, $message));
		self::$log[] = [$message, $level, $facility, time()];
	}

	static function logFlush() {
		if(!empty(self::$log)) log\Logger::kernelLog(self::$logConf, self::$log);
	}

	// === TRACE SYSTEM ===========================================================================

	/** backtrace store
	 * @var array */
	static protected $trace = [];
	/** framework internal backtrace Error level, incremented by errors & exceptions
	 * @var integer */
	static protected $traceError = 0;
	/** backtrace level
	 * @var integer */
	static protected $traceLevel = LOG_DEBUG;

	/**
	 * Kernel trace function.
	 * @param integer $level trace level, use a LOG_? constant value
	 * @param integer $type trace type, use a TRACE_? constant value
	 * @param string $function the tracing object method / function
	 * @param string $msg the trace message
	 * @param mixed $data the trace data
	 */
	static function trace($level=LOG_DEBUG, $type=TRACE_DEFAULT, $function, $msg=null, $data=null) {
		if($level > self::$traceLevel) return;
		self::$trace[] = [round(microtime(1)-$_SERVER['REQUEST_TIME_FLOAT'],5), memory_get_usage(), $level, $type, str_replace('metadigit','\\',$function), $msg, $data];
	}

	// === AUTOLOADING SYSTEM =====================================================================

	/**
	 * __autoload() implementation
	 * @param string $class class name
	 * @return void
	 */
	static function autoload($class) {
		list($namespace, $className, $dir, $file) = self::parseClassName($class);
		if(@file_exists($path = $dir.'/all.cache.inc')) {
			TRACE and self::trace(LOG_DEBUG, TRACE_AUTOLOADING, __METHOD__, $namespace.'\*');
			require_once($path);
			if(class_exists($class,0) || interface_exists($class,0) || trait_exists($class,0)) return;
		}
		if(@file_exists($file = $dir.'/'.$file.'.php')) {
			TRACE and self::trace(LOG_DEBUG, TRACE_AUTOLOADING, __METHOD__, $class);
			require($file);
			if(class_exists($class,0) || interface_exists($class,0) || trait_exists($class,0)) return;
		}
		TRACE and self::trace(LOG_ERR, TRACE_ERROR, __METHOD__, 'FAILED loading '.$class);
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
