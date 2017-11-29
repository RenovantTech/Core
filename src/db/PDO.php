<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\db;
// @TODO use const metadigit\core\TMP_DIR;
use const metadigit\core\trace\T_DB;
use function metadigit\core\trace;
/**
 * PDO wrapper
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class PDO extends \PDO {

	/**
	 * @param string $id database ID
	 * @param integer|false $level trace level, use a LOG_? constant value, default LOG_INFO
	 * @param string $statement the SQL statement
	 * @param array|null $params
	 */
	static function trace($id, $level=LOG_INFO, $statement, array $params = null) {
		if($level===false) return;
		if(!empty($params)) {
			$keys = $values = [];
			foreach($params as $k=>$v) {
				$keys[] = (is_string($k)) ? '/:'.$k.'/' : '/[?]/';
				$values[] = (is_null($v)) ? 'NULL' : ((is_numeric($v)) ? $v : '"'.htmlentities($v).'"');
			}
			$statement = preg_replace($keys, $values, $statement, 1);
		}
		$msg = (strlen($statement)>100) ? substr($statement,0,100).'...' : $statement;
		trace($level, T_DB, sprintf('[%s] %s', $id, $msg), $statement);
	}

	/** database ID
	 * @var string */
	protected $_id;

	/**
	 * @param string $dsn      the Data Source Name, or DSN, contains the information required to connect to the database
	 * @param string $username the user name for the DSN string, optional for some PDO drivers
	 * @param string $password the password for the DSN string, optional for some PDO drivers
	 * @param array	 $options  a key=>value array of driver-specific connection options
	 * @param string $id database ID, default "master"
	 */
	function __construct($dsn, $username=null, $password=null, array $options=null, $id='master') {
		$this->_id =$id;
		$options = (array) $options + [
			\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
			\PDO::ATTR_STATEMENT_CLASS => ['metadigit\core\db\PDOStatement', [ $id ]]
		];
		parent::__construct($dsn, $username, $password, $options);
		// SqLite specific settings
		if('sqlite'==$this->getAttribute(\PDO::ATTR_DRIVER_NAME)) {
			// @TODO if(file_exists(TMP_DIR.$id.'.vacuum')) unlink(TMP_DIR.$id.'.vacuum') && $this->exec('VACUUM');
			$this->exec('PRAGMA journal_mode = WAL');
			$this->exec('PRAGMA temp_store = MEMORY');
			$this->exec('PRAGMA synchronous = OFF');
			$this->exec('PRAGMA foreign_keys = ON');
		}
	}

	/**
	 * @see http://www.php.net/manual/en/pdo.begintransaction.php
	 * @return boolean TRUE on success
	 */
	function beginTransaction() {
		trace(LOG_INFO, T_DB, sprintf('[%s] beginTransaction()', $this->_id));
		return parent::beginTransaction();
	}

	/**
	 * @see http://www.php.net/manual/en/pdo.commit.php
	 * @return boolean TRUE on success
	 */
	function commit() {
		trace(LOG_INFO, T_DB, sprintf('[%s] commit()', $this->_id));
		return parent::commit();
	}

	/**
	 * @see http://www.php.net/manual/en/pdo.exec.php
	 * @param string $statement the SQL statement to prepare and execute
	 * @param integer|false $traceLevel trace level, use a LOG_? constant value, default LOG_INFO
	 * @return int the number of rows that were modified or deleted by the SQL statement
	 */
	function exec($statement, $traceLevel=LOG_INFO) {
		$msg = (strlen($statement)>100) ? substr($statement,0,100).'...' : $statement;
		trace($traceLevel, T_DB, sprintf('[%s] %s', $this->_id, $msg), $statement);
		return parent::exec($statement);
	}

	/**
	 * @see http://www.php.net/manual/en/pdo.prepare.php
	 * @param string $statement a valid SQL statement template
	 * @param array $options holds one or more key=>value pairs to set attribute values for the PDOStatement object
	 * @return PDOStatement
	 * @throws \PDOException
	 */
	function prepare($statement, $options = null) {
		$st = parent::prepare($statement, (array)$options);
		/** @var PDOStatement $st */
		return $st;
	}

	/**
	 * @see http://www.php.net/manual/en/pdo.exec.php
	 * @param string $statement the SQL statement to prepare and execute.
	 * @param integer|false $traceLevel trace level, use a LOG_? constant value, default LOG_INFO
	 * @return PDOStatement
	 */
	function query($statement, $traceLevel=LOG_INFO) {
		trace(LOG_DEBUG, T_DB, sprintf('[%s] %s', $this->_id, $statement));
		$st = parent::query($statement);
		if(func_num_args()>1) {
			$args = array_shift(func_get_args());
			call_user_func_array([$st,'setFetchMode'], $args);
		}
		/** @var PDOStatement $st */
		return $st;
	}

	/**
	 * @see http://www.php.net/manual/en/pdo.rollback.php
	 * @return boolean TRUE on success
	 */
	function rollBack() {
		trace(LOG_INFO, T_DB, sprintf('[%s] rollBack()', $this->_id));
		return parent::rollBack();
	}
}