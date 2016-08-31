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
 * PDOStatement
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
	 * @param array $input_parameters
	 * @return boolean TRUE on success
	 */
	function execute($input_parameters = null) {
		TRACE and trace(LOG_DEBUG, TRACE_DB, sprintf('[%s] %s', $this->_id, $this->queryString), $input_parameters);
		return parent::execute($input_parameters);
	}
}
