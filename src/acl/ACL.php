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

	/** DB settings
	 * @var array */
	protected $dbConf = [
		't_prefix'	=> 'sys_acl',
		't_users'	=> 'sys_users',
		't_groups'	=> 'sys_groups',
		't_u2g'		=> 'sys_users_2_groups'
	];

	/**
	 * ACL constructor.
	 * @param string $pdo PDO instance ID
	 * @param array $dbConf DB settings
	 */
	function __construct($pdo, array $dbConf) {
		$this->pdo = $pdo;
		$this->dbConf = $dbConf;
		TRACE and $this->trace(LOG_DEBUG, 1, __METHOD__, 'initialize ACL storage');
		$PDO = Kernel::pdo($pdo);
		$driver = $PDO->getAttribute(\PDO::ATTR_DRIVER_NAME);
		$PDO->exec(str_replace(
			['acl', 't_u2g', 't_users', 't_groups'],
			[$dbConf['t_prefix'], $dbConf['t_u2g'], $dbConf['t_users'], $dbConf['t_groups']],
			file_get_contents(__DIR__.'/sql/init-'.$driver.'.sql')
		));
	}

	function onRoute(Request $Req) {
		$target = $Req->URI();
		$method =$Req->getMethod();
		$acl = null;
		$aclArray = $this->pdoStExecute(
			'SELECT * FROM '.$this->dbConf['t_prefix'].' WHERE type = "URL" AND method = :method ORDER BY CHAR_LENGTH(target) DESC',
			['method'=>$method]
		)->fetchAll(\PDO::FETCH_ASSOC);
		foreach($aclArray as $item) {
			$item['target'] = str_replace('/', '\\/', $item['target']);
			if(preg_match('/'.$item['target'].'/', $target)) {
				$acl = $item;
				break;
			}
		}
		return ($acl) ? $this->checkAcl($acl) : true;
	}

	function onObject($target, $method) {
		$acl = $this->pdoStExecute(
			'SELECT * FROM '.$this->dbConf['t_prefix'].' WHERE type = "OBJECT" AND target = :target AND method = :method',
			['target'=>$target, 'method'=>$method]
		)->fetch(\PDO::FETCH_ASSOC);
		return ($acl) ? $this->checkAcl($acl) : true;
	}

	function onOrm($target, $method) {
		$acl = $this->pdoStExecute(
			'SELECT * FROM '.$this->dbConf['t_prefix'].' WHERE type = "ORM" AND target = :target AND method = :method',
			['target'=>$target, 'method'=>$method]
		)->fetch(\PDO::FETCH_ASSOC);
		return ($acl) ? $this->checkAcl($acl) : true;
	}

	protected function checkAcl(array $acl) {
		TRACE and $this->trace(LOG_DEBUG, 1, __METHOD__, "$acl[type] $acl[target] $acl[method]");
//echo "\n UID: $_SESSION[UID] - ACL: $acl[type] $acl[target] $acl[method] \n";
		if($acl['action']) {
			$actionName = $this->pdoQuery('SELECT name FROM '.$this->dbConf['t_prefix'].'_actions WHERE id = '.$acl['action'])->fetchColumn();
			if(
				!$this->pdoStExecute(
					'SELECT COUNT(*) FROM '.$this->dbConf['t_prefix'].'_actions_2_users WHERE action_id = :action_id AND user_id = :user_id',
					['action_id'=>$acl['action'], 'user_id'=>$_SESSION['UID']]
				)->fetchColumn()
				&&
				!$this->pdoStExecute(
					'SELECT COUNT(*) FROM '.$this->dbConf['t_prefix'].'_actions_2_groups WHERE action_id = :action_id AND group_id IN (SELECT group_id FROM '.$this->dbConf['t_u2g'].' WHERE user_id = :user_id )',
					['action_id'=>$acl['action'], 'user_id'=>$_SESSION['UID']]
				)->fetchColumn()
			) {
//echo "\t ACTION [$acl[action]] $actionName => EXCEPTION 100 \n";
				throw new Exception(100, [$actionName]);
			} else {
//echo "\t ACTION [$acl[action]] $actionName => OK \n";
			}
		}
		if($acl['filter']) {
			$filterName = $this->pdoQuery('SELECT name FROM '.$this->dbConf['t_prefix'].'_filters WHERE id = '.$acl['filter'])->fetchColumn();
			$values1 = (array) $this->pdoStExecute(
				'SELECT val FROM '.$this->dbConf['t_prefix'].'_filters_2_users WHERE filter_id = :filter_id AND user_id = :user_id',
				['filter_id'=>$acl['filter'], 'user_id'=>$_SESSION['UID']]
			)->fetchAll(\PDO::FETCH_COLUMN);

			$values2 = (array) $this->pdoStExecute(
				'SELECT val FROM '.$this->dbConf['t_prefix'].'_filters_2_groups WHERE filter_id = :filter_id AND group_id IN (SELECT group_id FROM '.$this->dbConf['t_u2g'].' WHERE user_id = :user_id )',
				['filter_id'=>$acl['filter'], 'user_id'=>$_SESSION['UID']]
			)->fetchAll(\PDO::FETCH_COLUMN);

			$values = array_merge($values1, $values2);
//var_dump($values);
			if(empty($values)) {
//echo "\t FILTER [$acl[filter]] $filterName => EXCEPTION 200 \n";
				throw new Exception(200, [$filterName]);
			} elseif(array_search('*', $values) !== false) {
//echo "\t FILTER [$acl[filter]] $filterName * => OK \n";
				return true;
			} else {
//echo "\t FILTER [$acl[filter]] $filterName VALUES: ".implode(' ', $values)." \n";
			$query = $this->pdoQuery('SELECT query FROM '.$this->dbConf['t_prefix'].'_filters_sql WHERE id = '.$acl['filter_sql'])->fetchColumn();
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
							throw new Exception(201, [$filterName, $query]);
						}
				}
			}
			return false;
		}
		return true;
	}
}
