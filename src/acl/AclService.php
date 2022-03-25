<?php
namespace renovant\core\acl;
use const renovant\core\trace\T_INFO;
use renovant\core\sys,
	renovant\core\http\Request;

class AclService {
	use \renovant\core\CoreTrait;

	const CACHE_PREFIX	= 'sys.acl.';

	const MOD_ORM		= 'ORM';
	const MOD_ROUTING	= 'ROUTING';
	const MOD_SERVICES	= 'SERVICES';

	const SQL_FETCH_USER_ACL = 'SELECT type, code, query FROM %s WHERE id IN (%s)';
	const SQL_FETCH_USER_MAPS = 'SELECT type, acl_id, data FROM %s_maps WHERE user_id = :user_id';


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

	/** Cache ID */
	protected string $cache = 'sys';
	/** Cache entry prefix */
	protected string $cachePrefix = self::CACHE_PREFIX;
	/** ACL modules to activate */
	protected array $modules = [];
	/** PDO instance ID */
	protected string $pdo;
	/** DB tables
	 * @var array */
	protected $tables = [
		'acl'	=> 'sys_acl',
		'users'	=> 'sys_users'
	];

	/**
	 * @param array $modules ACL modules to activate
	 * @param string|null $pdo PDO instance ID
	 * @param array|null $tables
	 */
	function __construct(array $modules, ?string $pdo=null, array $tables=null) {
		$prevTraceFn = sys::traceFn('sys.ACL');
		try {
			$this->modules = $modules;
			$this->pdo = $pdo;
			if ($tables) $this->tables = array_merge($this->tables, $tables);
			sys::trace(LOG_DEBUG, T_INFO, 'initialize ACL storage');
			$PDO = sys::pdo($this->pdo);
			$driver = $PDO->getAttribute(\PDO::ATTR_DRIVER_NAME);
			$PDO->exec(str_replace(
				['t_acl', 't_users'],
				[$this->tables['acl'], $this->tables['users']],
				file_get_contents(__DIR__ . '/sql/init-' . $driver . '.sql')
			));
			$this->__wakeup();
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	function __wakeup() {
		sys::trace(LOG_DEBUG, T_INFO, 'activating modules '.implode(', ', $this->modules), null, $this->_.'->init');
		foreach ($this->modules as $mod)
			define('SYS_ACL_'.strtoupper($mod), true);
	}

	/**
	 * Initialize ACL modules & User ACL.
	 * To be invoked via event listener before HTTP Routing execution (HTTP:INIT or HTTP:ROUTE).
	 * @throws \ReflectionException
	 */
	function init() {
		$Auth = sys::auth();
		if($Auth->UID()) {
			if(!$ACL = sys::cache($this->cache)->get($this->cachePrefix.$Auth->UID())) {
				$ACL = ACL::instance();
				$actions = $filters = $roles = [];

				$mapsArray = sys::pdo($this->pdo)
					->prepare(sprintf(self::SQL_FETCH_USER_MAPS, $this->tables['acl']))
					->execute(['user_id'=>$Auth->UID()])->fetchAll(\PDO::FETCH_ASSOC);
				$aclIds = [];
				foreach ($mapsArray as $map) {
					$aclIds[] = $map['acl_id'];
				}
				if(!empty($aclIds)) {
					$aclArray = sys::pdo($this->pdo)
						->prepare(sprintf(self::SQL_FETCH_USER_ACL, $this->tables['acl'], implode(',', $aclIds)))
						->execute()->fetchAll(\PDO::FETCH_ASSOC);
					foreach ($aclArray as $acl) {
						switch ($acl['type']) {
							case 'ACTION': $actions[] = $acl['code']; break;
							case 'FILTER': $filters[$acl['code']] = $acl['query']; break;
							case 'ROLE': $roles[] = $acl['code']; break;
						}
					}
				}
				$RConstructor = (new \ReflectionClass(ACL::class))->getConstructor();
				$RConstructor->setAccessible(true);
				$RConstructor->invokeArgs($ACL, [$actions, $filters, $roles]);
			}
		}
	}

	/**
	 * Apply ACL checks during HTTP routing
	 * @param Request $Req
	 * @param integer $userId User ID
	 * @return bool
	 * @throws \Exception
	 */
	function onRoute(Request $Req, $userId) {
		$prevTraceFn = sys::traceFn($this->_.'->'.__FUNCTION__);
		try {
			$target = $Req->URI();
			$method = $Req->getMethod();
			$matches = [];
			$aclArray = sys::pdo($this->pdo)
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
			return true;
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	/**
	 * Apply ACL checks during Services invocation
	 * @param string $target  object ID
	 * @param string $method  object method
	 * @param integer $userId User ID
	 * @return bool
	 * @throws \Exception
	 */
	function onObject($target, $method, $userId) {
		$prevTraceFn = sys::traceFn($this->_.'->'.__FUNCTION__);
		try {
			$matches = sys::pdo($this->pdo)
				->prepare(sprintf(self::SQL_CHECK_OBJECT, $this->tables['acl']))
				->execute(['target'=>$target, 'method'=>$method])->fetchAll(\PDO::FETCH_ASSOC);
			foreach($matches as $acl) {
//echo "\n UID: $userId - ACL: $acl[type] $acl[target] $acl[method] \n";
				if($acl && !empty($acl['action'])) $this->checkAction($acl, $userId);
				if($acl && !empty($acl['filter'])) $this->checkFilter($acl, $userId);
			}
			return true;
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	/**
	 * Apply ACL checks during ORM operations
	 * @param string $target Repository ID
	 * @param string $method Repository action (CREATE, FETCH .. )
	 * @param integer $userId User ID
	 * @return bool
	 * @throws \Exception
	 */
	function onOrm($target, $method, $userId) {
		$prevTraceFn = sys::traceFn($this->_.'->'.__FUNCTION__);
		try {
			$matches = sys::pdo($this->pdo)
				->prepare(sprintf(self::SQL_CHECK_ORM, $this->tables['acl']))
				->execute(['target'=>$target, 'method'=>$method])->fetchAll(\PDO::FETCH_ASSOC);
			foreach($matches as $acl) {
//echo "\n UID: $userId - ACL: $acl[type] $acl[target] $acl[method] \n";
				if($acl && !empty($acl['action'])) $this->checkAction($acl, $userId);
				if($acl && !empty($acl['filter'])) $this->checkFilter($acl, $userId);
			}
			return true;
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	/**
	 * @param array $acl
	 * @param $userId
	 * @throws Exception
	 */
	protected function checkAction(array $acl, $userId) {
		$actionCode = sys::pdo($this->pdo)->query(sprintf(self::SQL_FETCH_ACTION_CODE, $this->tables['acl'], $acl['action']))->fetchColumn();
		if(
			!sys::pdo($this->pdo)->prepare(sprintf(self::SQL_MATCH_ACTION_USER, $this->tables['acl']))
				->execute(['action_id'=>$acl['action'], 'user_id'=>$userId])->fetchColumn()
			&&
			!sys::pdo($this->pdo)->prepare(sprintf(self::SQL_MATCH_ACTION_GROUP, $this->tables['acl'], $this->tables['u2r']))
				->execute(['action_id'=>$acl['action'], 'user_id'=>$userId])->fetchColumn()
		) {
//echo "\t ACTION [$acl[action]] $actionCode => EXCEPTION 100 \n";
			throw new Exception(100, [$actionCode]);
		} else {
			sys::trace(LOG_DEBUG, T_INFO, "$acl[type] $acl[target] $acl[method] - ACTION: $actionCode => OK ");
//echo "\t ACTION [$acl[action]] $actionCode => OK \n";
		}
	}

	/**
	 * @param array $acl
	 * @param $userId
	 * @return bool
	 * @throws Exception
	 */
	protected function checkFilter(array $acl, $userId) {
		$filterCode = sys::pdo($this->pdo)->query(sprintf(self::SQL_FETCH_FILTER_CODE, $this->tables['acl'], $acl['filter']))->fetchColumn();
		$values1 = (array) sys::pdo($this->pdo)->prepare(sprintf(self::SQL_MATCH_FILTER_USER, $this->tables['acl']))
			->execute(['filter_id'=>$acl['filter'], 'user_id'=>$userId])->fetchAll(\PDO::FETCH_COLUMN);

		$values2 = (array) sys::pdo($this->pdo)->prepare(sprintf(self::SQL_MATCH_FILTER_GROUP, $this->tables['acl'], $this->tables['u2r']))
			->execute(['filter_id'=>$acl['filter'], 'user_id'=>$userId])->fetchAll(\PDO::FETCH_COLUMN);

		$values = array_merge($values1, $values2);
		if(empty($values)) {
//echo "\t FILTER [$acl[filter]] $filterCode => EXCEPTION 200 \n";
			throw new Exception(200, [$filterCode]);
		} elseif(array_search('*', $values) !== false) {
			sys::trace(LOG_DEBUG, T_INFO, "$acl[type] $acl[target] $acl[method] - FILTER: $filterCode VALUE: * => OK ");
//echo "\t FILTER [$acl[filter]] $filterCode * => OK \n";
			return true;
		} else {
//echo "\t FILTER [$acl[filter]] $filterCode VALUES: ".implode(' ', $values)." \n";
			$query = sys::pdo($this->pdo)->query(sprintf(self::SQL_FETCH_QUERY, $this->tables['acl'], $acl['filter_sql']))->fetchColumn();
//echo "\t QUERY: $query \n";

			// parse query params
			$params = null;

			// execute
			switch($acl['type']) {
				case 'ORM':
					return true;
					break;
				default:
					if($r = sys::pdo($this->pdo)->prepare($query)->execute($params)->fetchColumn()) {
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
