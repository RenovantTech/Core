<?php
namespace renovant\core\authz;
class AuthzException extends \renovant\core\Exception {
	// INIT phase
	const COD1 = '[INIT] initialization yet done';
	// ACL
	const COD100 = '[ACL] "%s" missing for %s';
	const COD101 = '[ACL] "%s" missing for %s->%s()';
	// FILTERS
	const COD200 = '[FILTER] "%s" missing for %s';
	const COD201 = '[FILTER] "%s" missing for %s->%s()';
	// RBAC roles
	const COD300 = '[ROLE] "%s" missing for %s';
	const COD301 = '[ROLE] "%s" missing for %s->%s()';
	// RBAC permissions
	const COD400 = '[PERMISSION] "%s" missing for %s';
	const COD401 = '[PERMISSION] "%s" missing for %s->%s()';
}
