<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\db;
/**
 * PDOStatement wrapper
 * @author Daniele Sciacchitano <dan@metadigit.it>
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
	 * @see http://www.php.net/manual/en/pdostatement.execute.php
	 * @param array|null $params
	 * @param integer|false $traceLevel trace level, use a LOG_? constant value, default LOG_INFO
	 * @return boolean TRUE on success
	 */
	function execute($params = null, $traceLevel=LOG_INFO) {
		TRACE and PDO::trace($this->_id, $traceLevel, $this->queryString, $params);
		return parent::execute($params);
	}
}
