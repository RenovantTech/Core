<?php
namespace renovant\core\console;
use const renovant\core\trace\T_INFO;
use renovant\core\sys,
	renovant\core\util\reflection\ReflectionMethod,
	renovant\core\util\reflection\ReflectionProperty,
	renovant\core\util\str;

const SQLITE_INSERT = 'INSERT OR IGNORE INTO %s (id, class, namespace, description) VALUES (:id, :class, :namespace, :description)';
const SQLITE_UPDATE = 'UPDATE %s SET class = :class, namespace = :namespace, description = :description WHERE id = :id';
const MYSQL_INSERT = 'INSERT IGNORE INTO %s (id, class, namespace, description) VALUES (:id, :class, :namespace, :description)';
const MYSQL_UPDATE = 'UPDATE %s SET class = :class, namespace = :namespace, description = :description WHERE id = :id';


/**
 * @param string $pdo PDO instance ID
 * @param string $table table name
 */
function scan(string $pdo, string $table): void {

	$prevTraceFn = sys::traceFn(__METHOD__);
	try {
		sys::trace(LOG_DEBUG);
		$pdoInsert = $pdoUpdate = null;
		switch (sys::pdo($pdo)->getAttribute(\PDO::ATTR_DRIVER_NAME)) {
			case 'mysql':
				$pdoInsert = sys::pdo($pdo)->prepare(sprintf(MYSQL_INSERT, $table));
				$pdoUpdate = sys::pdo($pdo)->prepare(sprintf(MYSQL_UPDATE, $table));
				break;
			case 'sqlite':
				$pdoInsert = sys::pdo($pdo)->prepare(sprintf(SQLITE_INSERT, $table));
				$pdoUpdate = sys::pdo($pdo)->prepare(sprintf(SQLITE_UPDATE, $table));
				break;
		}
		$c = 0;
		$cache = [];
		$RefProperty = new ReflectionProperty(sys::class, 'routes');
		$RefProperty->setAccessible(true);
		$routes = $RefProperty->getValue(sys::class);
		$RefProperty->setAccessible(false);


		foreach ($routes as $app => $conf) {
			sys::trace(LOG_DEBUG, T_INFO, 'searching namespace ' . $conf['namespace']);
			sys::context()->init($conf['namespace']);
			$Dispatcher = sys::context()->container()->get($conf['namespace'] . '.Dispatcher');
			$RefPropMappings = new \ReflectionProperty($Dispatcher, 'routes');
			$RefPropMappings->setAccessible(true);
			$routes = $RefPropMappings->getValue($Dispatcher);
			foreach ($routes as $cmd2 => $controllerID) {
				$BatchController = sys::context()->container()->get($controllerID);
				if (!$BatchController instanceof ControllerInterface) continue;
				$params['class'] = get_class($BatchController);
				$params['namespace'] = $conf['namespace'];
				if ($BatchController instanceof \renovant\core\console\controller\ActionController) {
					$RefPropActions = new \ReflectionProperty($BatchController, '_config');
					$RefPropActions->setAccessible(true);
					$actions = $RefPropActions->getValue($BatchController);
					foreach ($actions as $action => $config) {
						$RefMethod = new ReflectionMethod($BatchController, $action);
						if ($RefMethod->getDocComment()->hasTag('batch')) {
							$params['id'] = $conf['cmd'] . ' ' . $cmd2 . ' ' . str::camel2kebab($action);
							$params['description'] = $RefMethod->getDocComment()->getTag('batch')['description'];
							$c++;
							sys::trace(LOG_DEBUG, T_INFO, '[STORE] ' . $params['id']);
							$pdoInsert->execute($params);
							$pdoUpdate->execute($params);
							$cache[] = $params['id'];
						}
					}
				} else {
					$handleMethod = ($BatchController instanceof \renovant\core\console\controller\AbstractController) ? 'doHandle' : 'handle';
					$RefMethod = new ReflectionMethod($BatchController, $handleMethod);
					if ($RefMethod->getDocComment()->hasTag('batch')) {
						$params['id'] = $conf['cmd'] . ' ' . $cmd2;
						$params['description'] = $RefMethod->getDocComment()->getTag('batch')['description'];
						$c++;
						sys::trace(LOG_DEBUG, T_INFO, '[STORE] ' . $params['id']);
						$pdoInsert->execute($params);
						$pdoUpdate->execute($params);
						$cache[] = $params['id'];
					}
				}
			}
		}

		// delete not existing batches
		sys::trace(LOG_DEBUG, T_INFO, 'clean old batches');
		$batches = sys::pdo($pdo)->query(sprintf('SELECT id FROM %s', $table))->fetchAll(\PDO::FETCH_COLUMN);
		foreach ($batches as $batchID) {
			if (!in_array($batchID, $cache)) {
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
