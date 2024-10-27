<?php
namespace renovant\core\authz;

class AuthzException extends \renovant\core\Exception {
	// INIT phase
	public const COD1 = '[INIT] initialization yet done';
	// ACL
	public const COD100 = '[ACL] "%s" missing for %s';
	public const COD101 = '[ACL] "%s" missing for %s->%s()';
	// RBAC roles
	public const COD300 = '[ROLE] "%s" missing for %s';
	public const COD301 = '[ROLE] "%s" missing for %s->%s()';
	// RBAC permissions
	public const COD400 = '[PERMISSION] "%s" missing for %s';
	public const COD401 = '[PERMISSION] "%s" missing for %s->%s()';

	// define/delete
	public const COD500 = 'VALIDATION error: %s';
	public const COD501 = '[DELETE] %s "%s" NOT DEFINED';
	public const COD502 = '[RENAME] %s "%s" NOT DEFINED';

	// set/fetch/revoke
	public const COD611 = '[SET] role "%s" NOT DEFINED';
	public const COD612 = '[SET] permission "%s" NOT DEFINED';
	public const COD613 = '[SET] acl "%s" NOT DEFINED';
	public const COD621 = '[FETCH] role "%s" NOT DEFINED';
	public const COD622 = '[FETCH] permission "%s" NOT DEFINED';
	public const COD623 = '[FETCH] acl "%s" NOT DEFINED';
	public const COD631 = '[REVOKE] role "%s" NOT DEFINED';
	public const COD632 = '[REVOKE] permission "%s" NOT DEFINED';
	public const COD633 = '[REVOKE] acl "%s" NOT DEFINED';
}
