<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\db;
use function metadigit\core\trace;
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
	 * @return boolean TRUE on success
	 */
	function execute($params = null) {
		if(TRACE) {
			$sql = $this->queryString;
			if(!empty($params)) {
				$keys = $values = [];
				foreach($params as $k=>$v) {
					$keys[] = (is_string($k)) ? '/:'.$k.'/' : '/[?]/';
					$values[] = (is_null($v)) ? 'NULL' : ((is_numeric($v)) ? $v : '"'.htmlentities($v).'"');
				}
				$sql = preg_replace($keys, $values, $sql, 1);
			}
			$msg = (strlen($sql)>100) ? substr($sql,0,100).'...' : $sql;
			trace(LOG_DEBUG, TRACE_DB, sprintf('[%s] %s', $this->_id, $msg), $sql);
		}
		return parent::execute($params);
	}
}
