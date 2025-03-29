<?php
namespace renovant\core\console;

use renovant\core\sys,
renovant\core\console\Dispatcher,
renovant\core\container\Container,
renovant\core\context\ContextHelper,
renovant\core\util\str;
use renovant\core\util\reflection\{ReflectionMethod, ReflectionProperty};

use const renovant\core\BIN_DIR;
use const renovant\core\trace\T_INFO;

class CmdScanner {
	protected const string SQLITE_INSERT = 'INSERT OR IGNORE INTO %s (id, class, namespace, description) VALUES (:id, :class, :namespace, :description)';
	protected const string SQLITE_UPDATE = 'UPDATE %s SET class = :class, namespace = :namespace, description = :description WHERE id = :id';
	protected const string MYSQL_INSERT  = 'INSERT IGNORE INTO %s (id, class, namespace, description) VALUES (:id, :class, :namespace, :description)';
	protected const string MYSQL_UPDATE  = 'UPDATE %s SET class = :class, namespace = :namespace, description = :description WHERE id = :id';

	protected static $controlles = [];
	protected static $pdoInsert;
	protected static $pdoUpdate;

	/**
	 * @param string $pdo PDO instance ID
	 * @param string $table table name
	 */
	public static function scan(string $pdo, string $table): void {
		$prevTraceFn = sys::traceFn(__METHOD__);
		try {
			sys::trace(LOG_DEBUG);
			switch (sys::pdo($pdo)->getAttribute(\PDO::ATTR_DRIVER_NAME)) {
				case 'mysql':
					self::$pdoInsert = sys::pdo($pdo)->prepare(sprintf(self::MYSQL_INSERT, $table));
					self::$pdoUpdate = sys::pdo($pdo)->prepare(sprintf(self::MYSQL_UPDATE, $table));
					break;
				case 'sqlite':
					self::$pdoInsert = sys::pdo($pdo)->prepare(sprintf(self::SQLITE_INSERT, $table));
					self::$pdoUpdate = sys::pdo($pdo)->prepare(sprintf(self::SQLITE_UPDATE, $table));
					break;
			}
			$c     = 0;
			$cache = [];

			// scan BIN_DIR
			sys::trace(LOG_DEBUG, T_INFO, ' scanning BIN_DIR ' . BIN_DIR);
			$files = scandir(BIN_DIR);
			foreach ($files as $file) {
				if (is_file(BIN_DIR . $file)) {
					$code  = file_get_contents(BIN_DIR . $file);
					$lines = file(BIN_DIR . $file);
					// check line 1 php path
					if (substr($lines[0], 0, 2) !== '#!') {
						continue;
					}
					// check last line with sys::run
					if (preg_match("/sys::run\('([a-zA-Z\.]*)'\)/", $lines[count($lines) - 1], $matches)) {
						sys::trace(LOG_DEBUG, T_INFO, ' sys::run() found into ' . BIN_DIR . $file . ' => context: ' . $matches[1]);
						$cmd1    = $file;
						$context = $matches[1];
						self::scanCmdContext($cmd1, $context);
					}
				}
			}

			// delete not existing batches
			sys::trace(LOG_DEBUG, T_INFO, 'clean old batches');
			$batches = sys::pdo($pdo)->query(sprintf('SELECT id FROM %s', $table))->fetchAll(\PDO::FETCH_COLUMN);
			foreach ($batches as $batchID) {
				if (!in_array($batchID, self::$controlles)) {
					sys::trace(LOG_DEBUG, T_INFO, '[CLEAN] ' . $batchID);
					sys::pdo($pdo)->exec(sprintf('DELETE FROM %s WHERE id = "%s"', $table, $batchID));
				}
			}
		} catch (\Exception $Ex) {
			trigger_error($Ex->getMessage());
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	protected static function scanCmdContext(string $cmd1, string $context) {
		$prevTraceFn = sys::traceFn(__METHOD__);
		try {
			sys::trace(LOG_DEBUG, T_INFO, 'CMD "' . $cmd1 . '" - scanning APP context ' . $context);
			sys::context()->init($context);
			if (!sys::context()->container()->has($context . '.AppCLI', App::class)) {
				sys::trace(LOG_DEBUG, T_INFO, 'no console\App instance found');
				return;
			}
			$App             = sys::context()->container()->get($context . '.AppCLI');
			$RefPropMappings = new \ReflectionProperty($App::class, 'modules');
			$RefPropMappings->setAccessible(true);
			$modules = $RefPropMappings->getValue($App);
			foreach ($modules as $modName => $conf) {
				$modContext = $conf['context'] ?? $context;
				self::scanModContext($cmd1, $modContext);
			}
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	protected static function scanModContext(string $cmd1, string $context) {
		static $parsed = [];
		$prevTraceFn   = sys::traceFn(__METHOD__);
		try {
			if (in_array($context, $parsed)) {
				return;
			}
			sys::trace(LOG_DEBUG, T_INFO, 'CMD "' . $cmd1 . '" - scanning MOD context ' . $context);
			$parsed[] = $context;
			sys::context()->init($context);
			if (!sys::context()->container()->has($context . '.Dispatcher', Dispatcher::class)) {
				sys::trace(LOG_DEBUG, T_INFO, 'no console\Dispatcher instance found');
				return;
			}
			sys::trace(LOG_DEBUG, T_INFO, 'console\Dispatcher instance found: ' . $context . '.Dispatcher');
			$App             = sys::context()->container()->get($context . '.Dispatcher');
			$RefPropMappings = new \ReflectionProperty($App::class, 'routes');
			$RefPropMappings->setAccessible(true);
			$routes = $RefPropMappings->getValue($App);
			foreach ($routes as $cmd2 => $Ctrl) {
				self::scanCtrl($cmd1, $cmd2, $Ctrl);
			}
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	protected static function scanCtrl(string $cmd1, string $cmd2, string $ctrl) {
		$prevTraceFn = sys::traceFn(__METHOD__);
		try {
			sys::trace(LOG_DEBUG, T_INFO, 'CMD "' . $cmd1 . ' ' . $cmd2 . '" - scanning CTRL ' . $ctrl);

			$BatchController = sys::context()->container()->get($ctrl);
			if (!$BatchController instanceof ControllerInterface) {
				return;
			}
			$params['class']     = get_class($BatchController);
			$params['namespace'] = strrchr($ctrl, '.', true);
			if ($BatchController instanceof \renovant\core\console\controller\ActionController) {
				$RefPropActions = new \ReflectionProperty($BatchController, '_config');
				$RefPropActions->setAccessible(true);
				$actions = $RefPropActions->getValue($BatchController);
				foreach ($actions as $action => $config) {
					$RefMethod = new ReflectionMethod($BatchController, $action);
					if ($RefMethod->getDocComment()->hasTag('batch')) {
						$params['id']          = $cmd1 . ' ' . $cmd2 . ' ' . str::camel2kebab($action);
						$params['description'] = $RefMethod->getDocComment()->getTag('batch')['description'];
						//						$c++;
						sys::trace(LOG_DEBUG, T_INFO, '[STORE] ' . $params['id']);
						self::$pdoInsert->execute($params);
						self::$pdoUpdate->execute($params);
						self::$controlles[] = $params['id'];
					}
				}
			} else {
				$handleMethod = ($BatchController instanceof \renovant\core\console\controller\AbstractController) ? 'doHandle' : 'handle';
				$RefMethod    = new ReflectionMethod($BatchController, $handleMethod);
				if ($RefMethod->getDocComment()->hasTag('batch')) {
					$params['id']          = $cmd1 . ' ' . $cmd2;
					$params['description'] = $RefMethod->getDocComment()->getTag('batch')['description'];
					//					$c++;
					sys::trace(LOG_DEBUG, T_INFO, '[STORE] ' . $params['id']);
					self::$pdoInsert->execute($params);
					self::$pdoUpdate->execute($params);
					self::$controlles[] = $params['id'];
				}
			}
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}
}
