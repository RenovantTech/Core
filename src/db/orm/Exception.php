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
	const COD100 = 'INSERT %s->%s() - PDOException: %s - %s';
	// R (read)
	const COD200 = 'SELECT %s->%s() - PDOException: %s - %s';
	// U (update)
	const COD300 = 'UPDATE %s->%s() - PDOException: %s - %s';
	// D (delete)
	const COD400 = 'DELETE %s->%s() - PDOException: %s - %s';
	// validation
	const COD500 = 'VALIDATION error: %s';
	// configuration (annotation)
	const COD602 = '%s invalid configuration: missing @orm tag into Entity class declaration';
	const COD603 = '%s invalid configuration: must have @orm(source="?") OR alternatives (target, insertFn, updateFn, deleteFn) into Entity class declaration';
	const COD604 = '%s invalid configuration: property "%s" has invalid tag @orm(type="%s")';
}
