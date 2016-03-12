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
echo "\n UID: $_SESSION[UID] - ACL: $acl[type] $acl[target] $acl[method] \n";
		if($acl['action']) {
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
			) throw new Exception(100);
		}
		return true;
	}
}
