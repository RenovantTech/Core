<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core;
use const metadigit\core\trace\{T_AUTOLOAD, T_ERROR, T_INFO};
use metadigit\core\context\Context,
	metadigit\core\trace\Tracer;
/**
 * Kernel
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Kernel {

	const CONFIG_ACL		= 0;
	const CONFIG_APP		= 1;
	const CONFIG_CACHE		= 2;
	const CONFIG_LOG		= 3;
	const CONFIG_NAMESPACE	= 4;
	const CONFIG_PDO		= 5;
	const EVENT_INIT		= 'kernel:init';
	const EVENT_SHUTDOWN	= 'kernel:shutdown';

	/** ACL configurations
	 * @var array */
	static protected $aclConf = [
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
	static protected $apps;
	/** Cache configurations
	 * @var array */
	static protected $cacheConf = [
		'kernel' => [
			'class' => 'metadigit\core\cache\SqliteCache',
			'params' => ['kernel-cache', 'cache', true]
		]
	];
	/** LogWriters configurations
	 * @var array */
	static protected $logConf;
	/** Php classes namespace definitions, used by __autoload()
	 * @var array */
	static protected $namespaces = [
		'metadigit\core' => DIR
	];
	/** Database PDO configurations
	 * @var array */
	static protected $pdoConf = [
		'kernel-cache' => [ 'dns' => 'sqlite:'.CACHE_DIR.'kernel-cache.sqlite' ],
		'kernel-trace' => [ 'dns' => 'sqlite:'.DATA_DIR.'kernel-trace.sqlite' ]
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
	/** System Context
	 * @var \metadigit\core\context\Context */
	static protected $SystemContext;

	/**
	 * Kernel config reader
	 * @param string $conf config name
	 * @return array|bool
	 */
	static function conf($conf) {
		switch ($conf) {
			case self::CONFIG_ACL: return self::$aclConf; break;
			case self::CONFIG_APP: return self::$apps; break;
			case self::CONFIG_CACHE: return self::$cacheConf; break;
			case self::CONFIG_LOG: return self::$logConf; break;
			case self::CONFIG_NAMESPACE: return self::$namespaces; break;
			case self::CONFIG_PDO: return self::$pdoConf; break;
			default: return false;
		}
	}

	/**
	 * Kernel bootstrap.
	 * It has the following functions:
	 * * set user defined constants;
	 * * set global php settings (TimeZone, charset);
	 * * initialize classes auto-loading;
	 * - register error & exception handlers.
	 * @return void
	 */
	static function init() {
		Tracer::traceFn(__METHOD__);
		TRACE and trace(LOG_DEBUG, T_INFO);
		// ENVIRONMENT FIX
		if(isset($_SERVER['REDIRECT_PORT'])) $_SERVER['SERVER_PORT'] = $_SERVER['REDIRECT_PORT'];
		ignore_user_abort(1);
		ini_set('upload_tmp_dir', TMP_DIR);
		spl_autoload_register(__CLASS__.'::autoload');
		register_shutdown_function(__CLASS__.'::shutdown');

		$config = yaml(CORE_YAML);

		// settings
		self::$settings = array_replace(self::$settings, $config['settings']);
		date_default_timezone_set(self::$settings['timeZone']);
		setlocale(LC_ALL,self::$settings['locale']);
		ini_set('default_charset',self::$settings['charset']);
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
		// ACL
		if(is_array($config['acl'])) self::$aclConf = array_merge(self::$aclConf, $config['acl']);
		define(__NAMESPACE__.'\ACL_ROUTES', (boolean) self::$aclConf['routes']);
		define(__NAMESPACE__.'\ACL_OBJECTS', (boolean) self::$aclConf['objects']);
		define(__NAMESPACE__.'\ACL_ORM', (boolean) self::$aclConf['orm']);
		// caches
		if(is_array($config['caches'])) self::$cacheConf = array_merge($config['caches'], self::$cacheConf);
		// databases
		if(is_array($config['databases'])) self::$pdoConf = array_merge($config['databases'], self::$pdoConf);
		// logs
		if(is_array($config['logs'])) self::$logConf = $config['logs'];

		// initialize
		if(!file_exists(DATA_DIR.'.metadigit-core')) KernelHelper::boot();
		cache('kernel');
		set_exception_handler(function() {
			call_user_func_array('metadigit\core\KernelDebugger::onException', func_get_args());
		});
		set_error_handler(function() {
			if(error_reporting()===0) return;
			call_user_func_array('metadigit\core\KernelDebugger::onError', func_get_args());
		});
		if(ACL_ROUTES || ACL_OBJECTS || ACL_ORM) acl();
		self::$SystemContext = Context::factory('system');
		self::$SystemContext->trigger(self::EVENT_INIT);
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
		TRACE and trace(LOG_DEBUG, T_INFO, $dispatcherID);
		Context::factory($namespace)->get($dispatcherID)->dispatch(self::$Req, self::$Res);
	}

	/**
	 * Automatic shutdown handler
	 */
	static function shutdown() {
		Tracer::traceFn(__METHOD__);
		self::$SystemContext->trigger(self::EVENT_SHUTDOWN);
		cache\SqliteCache::shutdown();
		$err = error_get_last();
		if(in_array($err['type'], [E_ERROR,E_CORE_ERROR,E_COMPILE_ERROR,])) {
			Tracer::$traceError = KernelDebugger::E_ERROR;
			KernelDebugger::onError($err['type'], $err['message'], $err['file'], $err['line'], null);
			http_response_code(500);
		}
		if(PHP_SAPI != 'cli') session_write_close();
		self::logFlush();
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
		TRACE and trace(LOG_DEBUG, T_INFO, sprintf('[%s] %s: %s', log\Logger::LABELS[$level], $facility, $message), null, __METHOD__);
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

	// === AUTO-LOADING SYSTEM ====================================================================

	/**
	 * __autoload() implementation
	 * @param string $class class name
	 * @return void
	 */
	static function autoload($class) {
		list($namespace, $className, $dir, $file) = self::parseClassName($class);
		if(@file_exists($path = $dir.'/all.cache.inc')) {
			TRACE and trace(LOG_DEBUG, T_AUTOLOAD, $namespace.'\*', null, __METHOD__);
			require_once($path);
			if(class_exists($class,0) || interface_exists($class,0) || trait_exists($class,0)) return;
		}
		if(@file_exists($file = $dir.'/'.$file.'.php')) {
			TRACE and trace(LOG_DEBUG, T_AUTOLOAD, $class, null, __METHOD__);
			require($file);
			if(class_exists($class,0) || interface_exists($class,0) || trait_exists($class,0)) return;
		}
		TRACE and trace(LOG_ERR, T_ERROR, 'FAILED loading '.$class, null, __METHOD__);
		Tracer::$traceError = 3;
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
	 * @return array|false
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
		return false;
	}
}
