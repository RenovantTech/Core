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

	/** database ID
	 * @var string */
	protected $_id;

	/**
	 * @param string $dsn the Data Source Name, or DSN, contains the information required to connect to the database
	 * @param string|null $username the username for the DSN string, optional for some PDO drivers
	 * @param string|null $password the password for the DSN string, optional for some PDO drivers
	 * @param array|null $options a key=>value array of driver-specific connection options
	 * @param string $id database ID, default "master"
	 */
	function __construct(string $dsn, ?string $username=null, ?string $password=null, ?array $options=null, string $id='master') {
		$this->_id =$id;
		$options = (array) $options + [
			\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
			\PDO::ATTR_STATEMENT_CLASS => ['renovant\core\db\PDOStatement', [ $id ]]
		];
		parent::__construct($dsn, $username, $password, $options);
		// SqLite specific settings
		if('sqlite'==$this->getAttribute(\PDO::ATTR_DRIVER_NAME)) {
			// @TODO if(file_exists(TMP_DIR.$id.'.vacuum')) unlink(TMP_DIR.$id.'.vacuum') && $this->exec('VACUUM');
			// @FIXME "PRAGMA journal_mode = WAL" causing random corruption
			$this->exec('PRAGMA temp_store = MEMORY; PRAGMA synchronous = OFF; PRAGMA foreign_keys = ON');
		}
	}

	/**
	 * @see http://www.php.net/manual/en/pdo.begintransaction.php
	 * @return boolean TRUE on success
	 */
	function beginTransaction() {
		sys::trace(LOG_INFO, T_DB, sprintf('[%s] beginTransaction()', $this->_id));
		return parent::beginTransaction();
	}

	/**
	 * @see http://www.php.net/manual/en/pdo.commit.php
	 * @return boolean TRUE on success
	 */
	function commit() {
		sys::trace(LOG_INFO, T_DB, sprintf('[%s] commit()', $this->_id));
		return parent::commit();
	}

	/**
	 * @see http://www.php.net/manual/en/pdo.exec.php
	 * @param string $statement the SQL statement to prepare and execute
	 * @param integer $traceLevel trace level, use a LOG_? constant value, default LOG_INFO
	 * @return int the number of rows that were modified or deleted by the SQL statement
	 */
	function exec(string $statement, int $traceLevel=LOG_INFO) {
		$msg = (strlen($statement)>100) ? substr($statement,0,100).'...' : $statement;
		sys::trace($traceLevel, T_DB, sprintf('[%s] %s', $this->_id, $msg), $statement);
		return parent::exec($statement);
	}

	/**
	 * @see http://www.php.net/manual/en/pdo.prepare.php
	 * @throws \PDOException
	 */
	function prepare(string $query, array $options=[]) {
		return parent::prepare($query, $options);
	}

	/**
	 * @see http://www.php.net/manual/en/pdo.exec.php
	 */
	function query(string $query, ?int $fetchMode=null, mixed ...$fetchModeArgs) {
		sys::trace(LOG_INFO, T_DB, sprintf('[%s] %s', $this->_id, $query));
		$st = parent::query($query, $fetchMode);
		if(func_num_args()>1) {
			$array = func_get_args();
			$args = array_shift($array);
			call_user_func_array([$st,'setFetchMode'], $array);
		}
		/** @var PDOStatement $st */
		return $st;
	}

	/**
	 * @see http://www.php.net/manual/en/pdo.rollback.php
	 * @return boolean TRUE on success
	 */
	function rollBack() {
		sys::trace(LOG_INFO, T_DB, sprintf('[%s] rollBack()', $this->_id));
		return parent::rollBack();
	}
}
