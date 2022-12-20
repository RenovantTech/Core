<?php
namespace renovant\core\db;
class PDOStatement extends \PDOStatement {

	/** database ID */
	protected string $_id;

	protected function __construct(string $id='master') {
		$this->_id =$id;
	}

	/**
	 * Override with fluent interface
	 * @see http://www.php.net/manual/en/pdostatement.execute.php
	 * @param array|null $params
	 * @param integer $traceLevel trace level, use a LOG_? constant value, default LOG_INFO
	 * @return PDOStatement
	 */
	#[\ReturnTypeWillChange]
	function execute(array $params = null, int $traceLevel=LOG_INFO) {
		PDO::trace($this->_id, $traceLevel, $this->queryString, $params);
		parent::execute($params);
		return $this;
	}
}
