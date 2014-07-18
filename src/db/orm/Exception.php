<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\db\orm;
/**
 * ORM Exception
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Exception extends \metadigit\core\Exception {
	// C (create)
	const COD100 = 'INSERT {1}->{2}() - PDOException: {3} - {4}';
	// R (read)
	const COD200 = 'SELECT {1}->{2}() - PDOException: {3} - {4}';
	// U (update)
	const COD300 = 'UPDATE {1}->{2}() - PDOException: {3} - {4}';
	// D (delete)
	const COD400 = 'DELETE {1}->{2}() - PDOException: {3} - {4}';
	// configuration (annotation)
	const COD602 = '{1} invalid configuration: missing @orm tag into Entity class declaration';
	const COD603 = '{1} invalid configuration: must have @orm(source="?") OR alternatives (target, insertFn, updateFn, deleteFn) into Entity class declaration';
	const COD604 = '{1} invalid configuration: property "{2}" has invalid tag @orm(type="{3}")';
}