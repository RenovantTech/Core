<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\acl;
use metadigit\core\Kernel,
	metadigit\core\http\Request;

class ACL {
	use \metadigit\core\CoreTrait, \metadigit\core\db\PdoTrait;

	const SQL_CHECK_ROUTE	= 'SELECT * FROM %s WHERE type = "URL" AND ( method IS NULL OR method = :method ) ORDER BY CHAR_LENGTH(target) DESC';
	const SQL_CHECK_OBJECT	= 'SELECT * FROM %s WHERE type = "OBJECT" AND target = :target AND ( method IS NULL OR method = :method )';
	const SQL_CHECK_ORM		= 'SELECT * FROM %s WHERE type = "ORM" AND target = :target AND ( method IS NULL OR method = :method )';

	const SQL_MATCH_ACTION_USER		= 'SELECT COUNT(*) FROM %s_actions_2_users WHERE action_id = :action_id AND user_id = :user_id';
	const SQL_MATCH_ACTION_GROUP	= 'SELECT COUNT(*) FROM %s_actions_2_groups WHERE action_id = :action_id AND group_id IN ( SELECT group_id FROM %s WHERE user_id = :user_id )';
	const SQL_MATCH_FILTER_USER		= 'SELECT data FROM %s_filters_2_users WHERE filter_id = :filter_id AND user_id = :user_id';
	const SQL_MATCH_FILTER_GROUP	= 'SELECT data FROM %s_filters_2_groups WHERE filter_id = :filter_id AND group_id IN ( SELECT group_id FROM %s WHERE user_id = :user_id )';

	const SQL_FETCH_ACTION_CODE = 'SELECT code FROM %s_actions WHERE id = %u';
	const SQL_FETCH_FILTER_CODE = 'SELECT code FROM %s_filters WHERE id = %u';
	const SQL_FETCH_QUERY = 'SELECT query FROM %s_filters_sql WHERE id = %u';


	/** DB tables
	 * @var array */
	protected $tables = [
		'acl'	=> 'sys_acl',
		'users'	=> 'sys_users',
		'groups'=> 'sys_groups',
		'u2g'	=> 'sys_users_2_groups'
	];

	/**
	 * ACL constructor.
	 * @param string $pdo PDO instance ID
	 * @param array $tables DB tables
	 */
	function __construct($pdo, array $tables) {
		$this->pdo = $pdo;
		$this->tables = $tables;
		TRACE and $this->trace(LOG_DEBUG, 1, __METHOD__, 'initialize ACL storage');
		$PDO = Kernel::pdo($pdo);
		$driver = $PDO->getAttribute(\PDO::ATTR_DRIVER_NAME);
		$PDO->exec(str_replace(
			['acl', 't_u2g', 't_users', 't_groups'],
			[$tables['acl'], $tables['u2g'], $tables['users'], $tables['groups']],
			file_get_contents(__DIR__.'/sql/init-'.$driver.'.sql')
		));
	}

	function onRoute(Request $Req) {
		$target = $Req->URI();
		$method =$Req->getMethod();
		$matches = [];
		$aclArray = $this->pdoStExecute(sprintf(self::SQL_CHECK_ROUTE, $this->tables['acl']),
			['method'=>$method])->fetchAll(\PDO::FETCH_ASSOC);
		foreach($aclArray as $item) {
			$item['target'] = str_replace('/', '\\/', $item['target']);
			if(preg_match('/'.$item['target'].'/', $target)) {
				$matches[] = $item;
				break;
			}
		}
		foreach($matches as $acl) {
//echo "\n UID: $_SESSION[UID] - ACL: $acl[type] $acl[target] $acl[method] \n";
			if($acl && !empty($acl['action'])) $this->checkAction($acl);
			if($acl && !empty($acl['filter'])) $this->checkFilter($acl);
		}
		return true;
	}

	function onObject($target, $method) {
		$matches = $this->pdoStExecute(sprintf(self::SQL_CHECK_OBJECT, $this->tables['acl']),
			['target'=>$target, 'method'=>$method])->fetchAll(\PDO::FETCH_ASSOC);
		foreach($matches as $acl) {
//echo "\n UID: $_SESSION[UID] - ACL: $acl[type] $acl[target] $acl[method] \n";
			if($acl && !empty($acl['action'])) $this->checkAction($acl);
			if($acl && !empty($acl['filter'])) $this->checkFilter($acl);
		}
		return true;
	}

	function onOrm($target, $method) {
		$matches = $this->pdoStExecute(sprintf(self::SQL_CHECK_ORM, $this->tables['acl']),
			['target'=>$target, 'method'=>$method])->fetchAll(\PDO::FETCH_ASSOC);
		foreach($matches as $acl) {
//echo "\n UID: $_SESSION[UID] - ACL: $acl[type] $acl[target] $acl[method] \n";
			if($acl && !empty($acl['action'])) $this->checkAction($acl);
			if($acl && !empty($acl['filter'])) $this->checkFilter($acl);
		}
		return true;
	}

	protected function checkAction(array $acl) {
		$actionCode = $this->pdoQuery(sprintf(self::SQL_FETCH_ACTION_CODE, $this->tables['acl'], $acl['action']))->fetchColumn();
		if(
			!$this->pdoStExecute(sprintf(self::SQL_MATCH_ACTION_USER, $this->tables['acl']),
				['action_id'=>$acl['action'], 'user_id'=>$_SESSION['UID']])->fetchColumn()
			&&
			!$this->pdoStExecute(sprintf(self::SQL_MATCH_ACTION_GROUP, $this->tables['acl'], $this->tables['u2g']),
				['action_id'=>$acl['action'], 'user_id'=>$_SESSION['UID']])->fetchColumn()
		) {
//echo "\t ACTION [$acl[action]] $actionCode => EXCEPTION 100 \n";
			throw new Exception(100, [$actionCode]);
		} else {
			TRACE and $this->trace(LOG_DEBUG, 1, __METHOD__, "$acl[type] $acl[target] $acl[method] - ACTION: $actionCode => OK ");
//echo "\t ACTION [$acl[action]] $actionCode => OK \n";
		}
	}

	protected function checkFilter(array $acl) {
		$filterCode = $this->pdoQuery(sprintf(self::SQL_FETCH_FILTER_CODE, $this->tables['acl'], $acl['filter']))->fetchColumn();
		$values1 = (array) $this->pdoStExecute(sprintf(self::SQL_MATCH_FILTER_USER, $this->tables['acl']),
			['filter_id'=>$acl['filter'], 'user_id'=>$_SESSION['UID']])->fetchAll(\PDO::FETCH_COLUMN);

		$values2 = (array) $this->pdoStExecute(sprintf(self::SQL_MATCH_FILTER_GROUP, $this->tables['acl'], $this->tables['u2g']),
			['filter_id'=>$acl['filter'], 'user_id'=>$_SESSION['UID']])->fetchAll(\PDO::FETCH_COLUMN);

		$values = array_merge($values1, $values2);
//var_dump($values);
		if(empty($values)) {
//echo "\t FILTER [$acl[filter]] $filterCode => EXCEPTION 200 \n";
			throw new Exception(200, [$filterCode]);
		} elseif(array_search('*', $values) !== false) {
			TRACE and $this->trace(LOG_DEBUG, 1, __METHOD__, "$acl[type] $acl[target] $acl[method] - FILTER: $filterCode VALUE: * => OK ");
//echo "\t FILTER [$acl[filter]] $filterCode * => OK \n";
			return true;
		} else {
//echo "\t FILTER [$acl[filter]] $filterCode VALUES: ".implode(' ', $values)." \n";
			$query = $this->pdoQuery(sprintf(self::SQL_FETCH_QUERY, $this->tables['acl'], $acl['filter_sql']))->fetchColumn();
//echo "\t QUERY: $query \n";

			// parse qyery params
			$params = null;

			// execute
			switch($acl['type']) {
				case 'ORM':
					return true;
					break;
				default:
					if($r = $this->pdoStExecute($query, $params)->fetchColumn()) {
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
