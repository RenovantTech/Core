<?php
namespace renovant\core\authz;
use const renovant\core\trace\T_INFO;
use renovant\core\sys;

class AuthzService {
	use \renovant\core\CoreTrait;

	const CACHE_PREFIX	= 'sys.authz.';

	const SQL_FETCH_AUTHZ = 'SELECT id, type, code, query FROM %s WHERE id IN (%s)';
	const SQL_FETCH_AUTHZ_MAPS = 'SELECT type, authz_id, data FROM %s_maps WHERE user_id = :user_id';

	/** Cache ID */
	protected string $cache = 'sys';
	/** Cache entry prefix */
	protected string $cachePrefix = self::CACHE_PREFIX;
	/** PDO instance ID */
	protected string $pdo;
	/** DB tables
	 * @var array */
	protected $tables = [
		'authz'	=> 'sys_authz',
		'users'	=> 'sys_users'
	];

	/**
	 * @param string|null $pdo PDO instance ID
	 * @param array|null $tables
	 */
	function __construct(?string $pdo=null, array $tables=null) {
		$prevTraceFn = sys::traceFn($this->_);
		try {
			$this->pdo = $pdo;
			if ($tables) $this->tables = array_merge($this->tables, $tables);
			sys::trace(LOG_DEBUG, T_INFO, 'initialize AUTHZ storage');
			$PDO = sys::pdo($this->pdo);
			$driver = $PDO->getAttribute(\PDO::ATTR_DRIVER_NAME);
			$PDO->exec(str_replace(
				['t_authz', 't_users'],
				[$this->tables['authz'], $this->tables['users']],
				file_get_contents(__DIR__ . '/sql/init-' . $driver . '.sql')
			));
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	/**
	 * Initialize AUTHZ modules & User Authz.
	 * To be invoked via event listener before HTTP Routing execution (HTTP:INIT or HTTP:ROUTE).
	 * @throws AuthzException
	 */
	function init() {
		$prevTraceFn = sys::traceFn($this->_.'->init');
		try {
			$Auth = sys::auth();
			if($Auth->UID()) {
				if($data = sys::cache($this->cache)->get($this->cachePrefix.$Auth->UID())) {
					Authz::init(...$data);
				} else {
					$acl = $roles = $permissions = [];
					$mapsArray = sys::pdo($this->pdo)
						->prepare(sprintf(self::SQL_FETCH_AUTHZ_MAPS, $this->tables['authz']))
						->execute(['user_id'=>$Auth->UID()])->fetchAll(\PDO::FETCH_ASSOC);
					$authzIds = [];
					foreach ($mapsArray as $map)
						$authzIds[] = $map['authz_id'];
					if(!empty($authzIds)) {
						$authzArray = sys::pdo($this->pdo)
							->prepare(sprintf(self::SQL_FETCH_AUTHZ, $this->tables['authz'], implode(',', $authzIds)))
							->execute()->fetchAll(\PDO::FETCH_ASSOC);
						foreach ($authzArray as $authz) {
							switch ($authz['type']) {
								case 'ACL':
									$data = array_values(array_filter($mapsArray, function ($map) use ($authz) {
										return ($map['authz_id'] == $authz['id']);
									}, ARRAY_FILTER_USE_BOTH))[0]['data'];
									$acl[$authz['code']] = (array)json_decode($data);
									break;
								case 'ROLE': $roles[] = $authz['code']; break;
								case 'PERMISSION': $permissions[] = $authz['code']; break;
							}
						}
					}
					sys::cache($this->cache)->set($this->cachePrefix.$Auth->UID(), [$roles, $permissions, $acl], 0, 'authz');
					Authz::init($roles, $permissions, $acl);
				}
				sys::trace(LOG_DEBUG, T_INFO, 'AUTHZ initialized');
			}
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}
}
