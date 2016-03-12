<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\acl;
use metadigit\core\Kernel;

class ACL {
	use \metadigit\core\CoreTrait;

	/** DB settings
	 * @var array */
	protected $dbConf = [
		'pdo'		=> 'master',
		't_prefix'	=> 'sys_acl',
		't_users'	=> 'sys_users',
		't_groups'	=> 'sys_groups',
		't_u2g'		=> 'sys_users_2_groups'
	];

	/**
	 * ACL constructor.
	 * @param array $dbConf DB settings
	 */
	function __construct(array $dbConf) {
		TRACE and Kernel::trace(LOG_DEBUG, 1, __METHOD__, 'initialize ACL storage');
		$PDO = Kernel::pdo($dbConf['pdo']);
		$driver = $PDO->getAttribute(\PDO::ATTR_DRIVER_NAME);
		$PDO->exec(str_replace(
			['acl', 't_u2g', 't_users', 't_groups'],
			[$dbConf['t_prefix'], $dbConf['t_u2g'], $dbConf['t_users'], $dbConf['t_groups']],
			file_get_contents(__DIR__.'/sql/init-'.$driver.'.sql')
		));
	}
}
