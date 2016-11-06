<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\acl;
use const metadigit\core\trace\T_INFO;
use function metadigit\core\{pdo, trace};
use metadigit\core\http\Request,
	metadigit\core\trace\Tracer;

class ACL {

	const SQL_CHECK_ROUTE	= 'SELECT * FROM %s WHERE type = "URL" AND ( method IS NULL OR method = :method ) ORDER BY CHAR_LENGTH(target) DESC';
	const SQL_CHECK_OBJECT	= 'SELECT * FROM %s WHERE type = "OBJECT" AND target = :target AND ( method IS NULL OR method = :method )';
	const SQL_CHECK_ORM		= 'SELECT * FROM %s WHERE type = "ORM" AND target = :target AND ( method IS NULL OR method = :method )';

	const SQL_MATCH_ACTION_USER		= 'SELECT COUNT(*) FROM %s_actions_2_users WHERE action_id = :action_id AND user_id = :user_id';
	const SQL_MATCH_ACTION_GROUP	= 'SELECT COUNT(*) FROM %s_actions_2_roles WHERE action_id = :action_id AND role_id IN ( SELECT role_id FROM %s WHERE user_id = :user_id )';
	const SQL_MATCH_FILTER_USER		= 'SELECT data FROM %s_filters_2_users WHERE filter_id = :filter_id AND user_id = :user_id';
	const SQL_MATCH_FILTER_GROUP	= 'SELECT data FROM %s_filters_2_roles WHERE filter_id = :filter_id AND role_id IN ( SELECT role_id FROM %s WHERE user_id = :user_id )';

	const SQL_FETCH_ACTION_CODE = 'SELECT code FROM %s_actions WHERE id = %u';
	const SQL_FETCH_FILTER_CODE = 'SELECT code FROM %s_filters WHERE id = %u';
	const SQL_FETCH_QUERY = 'SELECT query FROM %s_filters_sql WHERE id = %u';


	/** PDO instance ID
	 * @var string */
	protected $pdo;
	/** DB tables
	 * @var array */
	protected $tables = [
		'acl'	=> 'sys_acl',
		'users'	=> 'sys_users',
		'roles'	=> 'sys_roles',
		'u2r'	=> 'sys_users_2_roles'
	];

	/**
	 * @param string $pdo PDO instance ID, default to "master"
	 * @param array|null $tables
	 */
	function __construct($pdo='master', array $tables=null) {
		$prevTraceFn = Tracer::traceFn();
		Tracer::traceFn('ACL');
		if($pdo) $this->pdo = $pdo;
		if($tables) $this->tables = array_merge($this->tables, $tables);
		trace(LOG_DEBUG, T_INFO, 'initialize ACL storage');
		$PDO = pdo($this->pdo);
		$driver = $PDO->getAttribute(\PDO::ATTR_DRIVER_NAME);
		$PDO->exec(str_replace(
			['acl', 't_u2r', 't_users', 't_roles'],
			[$this->tables['acl'], $this->tables['u2r'], $this->tables['users'], $this->tables['roles']],
			file_get_contents(__DIR__.'/sql/init-'.$driver.'.sql')
		));
		Tracer::traceFn($prevTraceFn);
	}

	/**
	 * @param Request $Req
	 * @param integer $userId User ID
	 * @return bool
	 * @throws \Exception
	 */
	function onRoute(Request $Req, $userId) {
		$prevTraceFn = Tracer::traceFn();
		Tracer::traceFn('ACL->'.__FUNCTION__);
		$target = $Req->URI();
		$method = $Req->getMethod();
		$matches = [];
		try {
			$aclArray = pdo($this->pdo)
				->prepare(sprintf(self::SQL_CHECK_ROUTE, $this->tables['acl']))
				->execute(['method'=>$method])->fetchAll(\PDO::FETCH_ASSOC);
			foreach($aclArray as $item) {
				$item['target'] = str_replace('/', '\\/', $item['target']);
				if(preg_match('/'.$item['target'].'/', $target)) {
					$matches[] = $item;
					break;
				}
			}
			foreach($matches as $acl) {
//echo "\n UID: $userId - ACL: $acl[type] $acl[target] $acl[method] \n";
				if($acl && !empty($acl['action'])) $this->checkAction($acl, $userId);
				if($acl && !empty($acl['filter'])) $this->checkFilter($acl, $userId);
			}
			Tracer::traceFn($prevTraceFn);
			return true;
		} catch (\Exception $Ex) {
			Tracer::traceFn($prevTraceFn);
			throw $Ex;
		}
	}

	/**
	 * @param string $target  object ID
	 * @param string $method  object method
	 * @param integer $userId User ID
	 * @return bool
	 * @throws \Exception
	 */
	function onObject($target, $method, $userId) {
		$prevTraceFn = Tracer::traceFn();
		Tracer::traceFn('ACL->'.__FUNCTION__);
		try {
			$matches = pdo($this->pdo)
				->prepare(sprintf(self::SQL_CHECK_OBJECT, $this->tables['acl']))
				->execute(['target'=>$target, 'method'=>$method])->fetchAll(\PDO::FETCH_ASSOC);
			foreach($matches as $acl) {
//echo "\n UID: $userId - ACL: $acl[type] $acl[target] $acl[method] \n";
				if($acl && !empty($acl['action'])) $this->checkAction($acl, $userId);
				if($acl && !empty($acl['filter'])) $this->checkFilter($acl, $userId);
			}
			Tracer::traceFn($prevTraceFn);
			return true;
		} catch (\Exception $Ex) {
			Tracer::traceFn($prevTraceFn);
			throw $Ex;
		}
	}

	/**
	 * @param string $target Repository ID
	 * @param string $method Repository action (CREATE, FETCH .. )
	 * @param integer $userId User ID
	 * @return bool
	 * @throws \Exception
	 */
	function onOrm($target, $method, $userId) {
		$prevTraceFn = Tracer::traceFn();
		Tracer::traceFn('ACL->'.__FUNCTION__);
		try {
			$matches = pdo($this->pdo)
				->prepare(sprintf(self::SQL_CHECK_ORM, $this->tables['acl']))
				->execute(['target'=>$target, 'method'=>$method])->fetchAll(\PDO::FETCH_ASSOC);
			foreach($matches as $acl) {
//echo "\n UID: $userId - ACL: $acl[type] $acl[target] $acl[method] \n";
				if($acl && !empty($acl['action'])) $this->checkAction($acl, $userId);
				if($acl && !empty($acl['filter'])) $this->checkFilter($acl, $userId);
			}
			Tracer::traceFn($prevTraceFn);
			return true;
		} catch (\Exception $Ex) {
			Tracer::traceFn($prevTraceFn);
			throw $Ex;
		}
	}

	protected function checkAction(array $acl, $userId) {
		$actionCode = pdo($this->pdo)->query(sprintf(self::SQL_FETCH_ACTION_CODE, $this->tables['acl'], $acl['action']))->fetchColumn();
		if(
			!pdo($this->pdo)->prepare(sprintf(self::SQL_MATCH_ACTION_USER, $this->tables['acl']))
				->execute(['action_id'=>$acl['action'], 'user_id'=>$userId])->fetchColumn()
			&&
			!pdo($this->pdo)->prepare(sprintf(self::SQL_MATCH_ACTION_GROUP, $this->tables['acl'], $this->tables['u2r']))
				->execute(['action_id'=>$acl['action'], 'user_id'=>$userId])->fetchColumn()
		) {
//echo "\t ACTION [$acl[action]] $actionCode => EXCEPTION 100 \n";
			throw new Exception(100, [$actionCode]);
		} else {
			trace(LOG_DEBUG, T_INFO, "$acl[type] $acl[target] $acl[method] - ACTION: $actionCode => OK ");
//echo "\t ACTION [$acl[action]] $actionCode => OK \n";
		}
	}

	protected function checkFilter(array $acl, $userId) {
		$filterCode = pdo($this->pdo)->query(sprintf(self::SQL_FETCH_FILTER_CODE, $this->tables['acl'], $acl['filter']))->fetchColumn();
		$values1 = (array) pdo($this->pdo)->prepare(sprintf(self::SQL_MATCH_FILTER_USER, $this->tables['acl']))
			->execute(['filter_id'=>$acl['filter'], 'user_id'=>$userId])->fetchAll(\PDO::FETCH_COLUMN);

		$values2 = (array) pdo($this->pdo)->prepare(sprintf(self::SQL_MATCH_FILTER_GROUP, $this->tables['acl'], $this->tables['u2r']))
			->execute(['filter_id'=>$acl['filter'], 'user_id'=>$userId])->fetchAll(\PDO::FETCH_COLUMN);

		$values = array_merge($values1, $values2);
		if(empty($values)) {
//echo "\t FILTER [$acl[filter]] $filterCode => EXCEPTION 200 \n";
			throw new Exception(200, [$filterCode]);
		} elseif(array_search('*', $values) !== false) {
			trace(LOG_DEBUG, T_INFO, "$acl[type] $acl[target] $acl[method] - FILTER: $filterCode VALUE: * => OK ");
//echo "\t FILTER [$acl[filter]] $filterCode * => OK \n";
			return true;
		} else {
//echo "\t FILTER [$acl[filter]] $filterCode VALUES: ".implode(' ', $values)." \n";
			$query = pdo($this->pdo)->query(sprintf(self::SQL_FETCH_QUERY, $this->tables['acl'], $acl['filter_sql']))->fetchColumn();
//echo "\t QUERY: $query \n";

			// parse qyery params
			$params = null;

			// execute
			switch($acl['type']) {
				case 'ORM':
					return true;
					break;
				default:
					if($r = pdo($this->pdo)->prepare($query)->execute($params)->fetchColumn()) {
//echo "\t QUERY $r => OK \n";
						return true;
					} else {
//echo "\t QUERY $r => EXCEPTION 201 \n";
						throw new Exception(201, [$filterCode, $query]);
					}
			}
		}
	}
}
