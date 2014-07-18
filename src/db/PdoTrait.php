<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\db;
use metadigit\core\Kernel;
/**
 * Trait for DB operations, supporting trace
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
trait PdoTrait {

	/** PDO instance ID
	 * @var string */
	protected $pdo = 'master';

	/**
	 * Get the raw PDO handle
	 * @return \PDO
	 */
	protected function pdo() {
		return Kernel::pdo($this->pdo);
	}

	/**
	 * Proxy to \PDO::beginTransaction()
	 * @see http://www.php.net/manual/en/pdo.begintransaction.php
	 * @return bool
	 */
	protected function pdoBeginTransaction() {
		$pdo = Kernel::pdo($this->pdo);
		TRACE and $this->trace(LOG_DEBUG, TRACE_DB, debug_backtrace(0, 2)[1]['function'], 'beginTransaction()');
		return $pdo->beginTransaction();
	}

	/**
	 * Proxy to \PDO::commit()
	 * @see http://www.php.net/manual/en/pdo.commit.php
	 * @return bool
	 */
	protected function pdoCommit() {
		$pdo = Kernel::pdo($this->pdo);
		TRACE and $this->trace(LOG_DEBUG, TRACE_DB, debug_backtrace(0, 2)[1]['function'], 'commit()');
		return $pdo->commit();
	}

	/**
	 * Proxy to \PDO::exec()
	 * @see http://www.php.net/manual/en/pdo.exec.php
	 * @param string $sql
	 * @return int
	 */
	protected function pdoExec($sql) {
		$pdo = Kernel::pdo($this->pdo);
		TRACE and $this->trace(LOG_DEBUG, TRACE_DB, debug_backtrace(0, 2)[1]['function'], $sql);
		return $pdo->exec($sql);
	}

	/**
	 * Proxy to \PDO::lastInsertId()
	 * @see http://www.php.net/manual/en/pdo.lastinsertid.php
	 * @return mixed
	 */
	protected function pdoLastInsertId() {
		return Kernel::pdo($this->pdo)->lastInsertId();
	}

	/**
	 * Proxy to \PDO::prepare()
	 * @see http://www.php.net/manual/en/pdo.prepare.php
	 * @param string $sql SQL statement
	 * @return \PDOStatement
	 */
	protected function pdoPrepare($sql) {
		$pdo = Kernel::pdo($this->pdo);
		return $pdo->prepare($sql);
	}

	/**
	 * Proxy to \PDO::query()
	 * @see http://www.php.net/manual/en/pdo.exec.php
	 * @param string $sql
	 * @return \PDOStatement
	 */
	protected function pdoQuery($sql) {
		$pdo = Kernel::pdo($this->pdo);
		TRACE and $this->trace(LOG_DEBUG, TRACE_DB, debug_backtrace(0, 2)[1]['function'], $sql);
		return $pdo->query($sql);
	}

	/**
	 * Proxy to \PDO::rollBack()
	 * @see http://www.php.net/manual/en/pdo.rollback.php
	 * @return bool
	 */
	protected function pdoRollBack() {
		$pdo = Kernel::pdo($this->pdo);
		TRACE and $this->trace(LOG_DEBUG, TRACE_DB, debug_backtrace(0, 2)[1]['function'], 'rollBack()');
		return $pdo->rollBack();
	}

	/**
	 * Concatenation of \PDO::prepare() & \PDOStatement->execute
	 * Prepare & Executes an SQL statement, returning a result set as a PDOStatement object
	 * @see http://www.php.net/manual/en/pdo.prepare.php
	 * @see http://www.php.net/manual/en/pdostatement.execute.php
	 * @param string $sql the SQL statement to prepare and execute.
	 * @param array|null $params PDOStatement parameters
	 * @return \PDOStatement
	 */
	protected function pdoStExecute($sql, $params=null) {
		$pdo = Kernel::pdo($this->pdo);
		TRACE and $this->_pdo_trace(debug_backtrace(0, 2)[1]['function'], $sql, $params);
		$st = $pdo->prepare($sql);
		$st->execute($params);
		return $st;
	}

	/**
	 * Trace message parsing SQL parameters
	 * @param string $func
	 * @param string $sql
	 * @param array|null $params optional array of PDO params, NULL if no params required
	 * @return void
	 */
	private function _pdo_trace($func, $sql, $params=null) {
		if(!empty($params)) {
			$keys = [];
			$values = [];
			foreach($params as $k=>$v) {
				$keys[] = (is_string($k)) ? '/:'.$k.'/' : '/[?]/';
				$values[] = (is_numeric($v)) ? $v : '"'.htmlentities($v).'"';
			}
			$sql = preg_replace($keys, $values, $sql, 1);
		}
		$msg = (strlen($sql)>100) ? substr($sql,0,100).'...' : $sql;
		$this->trace(LOG_DEBUG, TRACE_DB, $func, $msg, $sql);
	}
}