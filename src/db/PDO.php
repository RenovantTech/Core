<?php
namespace renovant\core\db;
// @TODO use const renovant\core\TMP_DIR;
use const renovant\core\trace\T_DB;
use renovant\core\sys;
class PDO extends \PDO {

	/**
	 * @param string $id database ID
	 * @param integer $level trace level, use a LOG_? constant value, default LOG_INFO
	 * @param string $statement the SQL statement
	 * @param array|null $params
	 */
	static function trace(string $id, int $level, string $statement, ?array $params=null) {
		if(!empty($params)) {
			$keys = $values = [];
			foreach($params as $k=>$v) {
				$keys[] = (is_string($k)) ? '/:'.$k.'/' : '/[?]/';
				$values[] = (is_null($v)) ? 'NULL' : ((is_numeric($v)) ? $v : '"'.htmlentities($v).'"');
			}
			$statement = preg_replace($keys, $values, $statement, 1);
		}
		$msg = (strlen($statement)>100) ? substr($statement,0,100).'...' : $statement;
		sys::trace($level, T_DB, sprintf('[%s] %s', $id, $msg), preg_replace('/(FROM|LEFT JOIN|RIGHT JOIN|JOIN|WHERE|SET|VALUES|ORDER BY)/', "\n$1", $statement));
	}

	/** database ID */
	protected string $_id;

	/**
	 * @param string $dsn the Data Source Name, or DSN, contains the information required to connect to the database
	 * @param string|null $username the username for the DSN string, optional for some PDO drivers
	 * @param string|null $password the password for the DSN string, optional for some PDO drivers
	 * @param array|null $options a key=>value array of driver-specific connection options
	 * @param string $id database ID, default "master"
	 */
	function __construct(string $dsn, ?string $username=null, ?string $password=null, ?array $options=null, string $id='master') {
		$this->_id = $id;
		$options = (array) $options + [
			\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
			\PDO::ATTR_STATEMENT_CLASS => [PDOStatement::class, [ $id ]]
		];
		parent::__construct($dsn, $username, $password, $options);
		// SqLite specific settings
		if('sqlite'==$this->getAttribute(\PDO::ATTR_DRIVER_NAME)) {
			// @TODO if(file_exists(TMP_DIR.$id.'.vacuum')) unlink(TMP_DIR.$id.'.vacuum') && $this->exec('VACUUM');
			// @FIXME "PRAGMA journal_mode = WAL" causing random corruption
			$this->exec('PRAGMA temp_store = MEMORY; PRAGMA synchronous = OFF; PRAGMA foreign_keys = ON');
		}
	}

	function beginTransaction(): bool {
		sys::trace(LOG_INFO, T_DB, sprintf('[%s] beginTransaction()', $this->_id));
		return parent::beginTransaction();
	}

	function commit(): bool {
		sys::trace(LOG_INFO, T_DB, sprintf('[%s] commit()', $this->_id));
		return parent::commit();
	}

	function exec(string $statement): int {
		$msg = (strlen($statement)>100) ? substr($statement,0,100).'...' : $statement;
		sys::trace(LOG_INFO, T_DB, sprintf('[%s] %s', $this->_id, $msg), $statement);
		return parent::exec($statement);
	}

	/**
	 * @throws \PDOException
	 */
	function prepare(string $query, array $options=[]): PDOStatement {
		return parent::prepare($query, $options);
	}

	function query(string $query, ?int $fetchMode=null, mixed ...$fetchModeArgs): PDOStatement {
		sys::trace(LOG_INFO, T_DB, sprintf('[%s] %s', $this->_id, $query));
		$st = parent::query($query, $fetchMode);
		if(func_num_args()>1) {
			$array = func_get_args();
			array_shift($array);
			call_user_func_array([$st,'setFetchMode'], $array);
		}
		return $st;
	}

	function rollBack(): bool {
		sys::trace(LOG_INFO, T_DB, sprintf('[%s] rollBack()', $this->_id));
		return parent::rollBack();
	}
}
