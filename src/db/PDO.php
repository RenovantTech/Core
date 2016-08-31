<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\db;
// @TODO use const metadigit\core\TMP_DIR;
use function metadigit\core\trace;
/**
 * PDO wrapper
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class PDO extends \PDO {

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
		TRACE and trace(LOG_DEBUG, TRACE_DB, sprintf('[%s] beginTransaction()', $this->_id));
		return parent::beginTransaction();
	}

	/**
	 * @see http://www.php.net/manual/en/pdo.commit.php
	 * @return boolean TRUE on success
	 */
	function commit() {
		TRACE and trace(LOG_DEBUG, TRACE_DB, sprintf('[%s] commit()', $this->_id));
		return parent::commit();
	}

	/**
	 * @see http://www.php.net/manual/en/pdo.exec.php
	 * @param string $statement the SQL statement to prepare and execute
	 * @return int the number of rows that were modified or deleted by the SQL statement
	 */
	function exec($statement) {
		if(TRACE) {
			$msg = (strlen($statement)>100) ? substr($statement,0,100).'...' : $statement;
			trace(LOG_DEBUG, TRACE_DB, sprintf('[%s] %s', $this->_id, $msg), $statement);
		}
		return parent::exec($statement);
	}

	/**
	 * @see http://www.php.net/manual/en/pdo.prepare.php
	 * @param string $statement a valid SQL statement template
	 * @param array $options holds one or more key=>value pairs to set attribute values for the PDOStatement object
	 * @return \PDOStatement
	 * @throws \PDOException
	 */
	function prepare($statement, $options = null) {
		return parent::prepare($statement, (array)$options);
	}

	/**
	 * @see http://www.php.net/manual/en/pdo.exec.php
	 * @param string $statement the SQL statement to prepare and execute.
	 * @return \PDOStatement
	 */
/* @TODO
 	function query($statement) {
		TRACE and trace(LOG_DEBUG, TRACE_DB, sprintf('[%s] %s', $this->_id, $statement));
		return call_user_func_array('parent::query', func_get_args());
	}*/

	/**
	 * @see http://www.php.net/manual/en/pdo.rollback.php
	 * @return boolean TRUE on success
	 */
	function rollBack() {
		TRACE and trace(LOG_DEBUG, TRACE_DB, sprintf('[%s] rollBack()', $this->_id));
		return parent::rollBack();
	}
}
