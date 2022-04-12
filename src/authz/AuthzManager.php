<?php
namespace renovant\core\authz;
use renovant\core\util\validator\Validator;
use const renovant\core\trace\T_INFO;
use renovant\core\sys,
	renovant\core\authz\orm\Def,
	renovant\core\authz\orm\Map;

class AuthzManager {
	use \renovant\core\CoreTrait;

	const CACHE_PREFIX	= 'sys.authz.';

	const SQL_DEF_INSERT = 'INSERT INTO %s (type, code, label, query) VALUES (:type, :code, :label, :query)';
	const SQL_DEF_UPDATE = 'UPDATE `%s` SET type = :type, code = :code, label = :label, query = :query WHERE id = :id';
	const SQL_DEF_DELETE = 'DELETE FROM `%s` WHERE id = :id';

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
	 * @throws \ReflectionException|AuthzException
	 */
	function createDef(array $data): bool {
		$prevTraceFn = sys::traceFn($this->_);
		try {
			$Def = new Def($data);
			$errors = Validator::validate($Def);
			if(empty($errors)) return (bool) sys::pdo($this->pdo)->prepare(sprintf(self::SQL_DEF_INSERT, $this->tables['authz']))
				->execute([ 'type'=>$Def->type, 'code'=>$Def->code, 'label'=>$Def->label, 'query'=>$Def->query ])->rowCount();
			else throw new AuthzException(500, [implode(', ',array_keys($errors))], $errors);
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	/**
	 * @throws \ReflectionException|AuthzException
	 */
	function updateDef(array $data): bool {
		$prevTraceFn = sys::traceFn($this->_);
		try {
			$Def = new Def($data);
			$errors = Validator::validate($Def);
			if(empty($errors)) return (bool) sys::pdo($this->pdo)->prepare(sprintf(self::SQL_DEF_UPDATE, $this->tables['authz']))
				->execute([ 'id'=>$Def->id, 'type'=>$Def->type, 'code'=>$Def->code, 'label'=>$Def->label, 'query'=>$Def->query ])->rowCount();
			else throw new AuthzException(500, [implode(', ',array_keys($errors))], $errors);
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	function deleteDef(int $id): bool {
		$prevTraceFn = sys::traceFn($this->_);
		try {
			return (bool) sys::pdo($this->pdo)->prepare(sprintf(self::SQL_DEF_DELETE, $this->tables['authz']))
				->execute([ 'id'=>$id ])->rowCount();
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}
}
