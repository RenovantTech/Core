<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\db;
/**
 * PDOStatement
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class PDOStatement extends \PDOStatement {

	function execute($params = null) {
		parent::execute($params);
	}
}
