<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\db;
/**
 * PDOStatement wrapper
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
class PDOStatement extends \PDOStatement {

	/** database ID
	 * @var string */
	protected $_id;

	/**
	 * PDOStatement constructor.
	 * @param string $id database ID, default "master"
	 */
	protected function __construct($id='master') {
		$this->_id =$id;
	}

	/**
	 * Override with fluent interface
	 * @see http://www.php.net/manual/en/pdostatement.execute.php
	 * @param array|null $params
	 * @param integer|false $traceLevel trace level, use a LOG_? constant value, default LOG_INFO
	 * @return PDOStatement
	 */
	function execute($params = null, $traceLevel=LOG_INFO) {
		PDO::trace($this->_id, $traceLevel, $this->queryString, $params);
		parent::execute($params);
		return $this;
	}
}
