<?php
namespace renovant\core\db\orm;

class Exception extends \renovant\core\Exception {
	// C (create)
	public const COD100 = 'INSERT %s - PDOException: %s - %s';
	// R (read)
	public const COD200 = 'SELECT %s - PDOException: %s - %s';
	// U (update)
	public const COD300 = 'UPDATE %s - PDOException: %s - %s';
	// D (delete)
	public const COD400 = 'DELETE %s - PDOException: %s - %s';
	// validation
	public const COD500 = 'VALIDATION error: %s';
	// configuration (annotation)
	public const COD602 = '%s invalid configuration: missing @orm tag into Entity class declaration';
	public const COD603 = '%s invalid configuration: must have @orm(source="?") OR alternatives (target, insertFn, updateFn, deleteFn) into Entity class declaration';
	public const COD604 = '%s invalid configuration: property "%s" has invalid tag @orm(type="%s")';
}
