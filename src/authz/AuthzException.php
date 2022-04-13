<?php
namespace renovant\core\authz;
class AuthzException extends \renovant\core\Exception {
	// INIT phase
	const COD1 = '[INIT] initialization yet done';
	// ACL
	const COD100 = '[ACL] "%s" missing for %s';
	const COD101 = '[ACL] "%s" missing for %s->%s()';
	// RBAC roles
	const COD300 = '[ROLE] "%s" missing for %s';
	const COD301 = '[ROLE] "%s" missing for %s->%s()';
	// RBAC permissions
	const COD400 = '[PERMISSION] "%s" missing for %s';
	const COD401 = '[PERMISSION] "%s" missing for %s->%s()';
	// def & maps invalid
	const COD500 = 'VALIDATION error: %s';

	// set/revoke
	const COD611 = '[SET] role "%s" NOT DEFINED';
	const COD612 = '[SET] permission "%s" NOT DEFINED';
	const COD613 = '[SET] acl "%s" NOT DEFINED';
	const COD621 = '[REVOKE] role "%s" NOT DEFINED';
	const COD622 = '[REVOKE] permission "%s" NOT DEFINED';
	const COD623 = '[REVOKE] acl "%s" NOT DEFINED';
}
