<?php
namespace renovant\core\acl;
class AclException extends \renovant\core\Exception {
	// ACTIONS
	const COD100 = '[ACTION] "%s" missing for %s';
	const COD101 = '[ACTION] "%s" missing for %s->%s()';
	// FILTERS
	const COD200 = '[FILTER] "%s" missing for %s';
	const COD201 = '[FILTER] "%s" missing for %s->%s()';
	// ROLES
	const COD300 = '[ROLE] "%s" missing for %s';
	const COD301 = '[ROLE] "%s" missing for %s->%s()';
}
