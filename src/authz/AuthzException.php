<?php
namespace renovant\core\authz;
class AuthzException extends \renovant\core\Exception {
	// INIT phase
	const COD1 = '[INIT] initialization yet done';
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
