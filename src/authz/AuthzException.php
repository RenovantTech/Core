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

	// define/delete
	const COD500 = 'VALIDATION error: %s';
	const COD501 = '[DELETE] %s "%s" NOT DEFINED';
	const COD502 = '[RENAME] %s "%s" NOT DEFINED';

	// set/fetch/revoke
	const COD611 = '[SET] role "%s" NOT DEFINED';
	const COD612 = '[SET] permission "%s" NOT DEFINED';
	const COD613 = '[SET] acl "%s" NOT DEFINED';
	const COD621 = '[FETCH] role "%s" NOT DEFINED';
	const COD622 = '[FETCH] permission "%s" NOT DEFINED';
	const COD623 = '[FETCH] acl "%s" NOT DEFINED';
	const COD631 = '[REVOKE] role "%s" NOT DEFINED';
	const COD632 = '[REVOKE] permission "%s" NOT DEFINED';
	const COD633 = '[REVOKE] acl "%s" NOT DEFINED';
}
