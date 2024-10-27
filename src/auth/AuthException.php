<?php
namespace renovant\core\auth;

use renovant\core\Exception;

class AuthException extends Exception {
	// COOKIE

	// JWT
	public const COD21 = 'JWT token INVALID';
	public const COD22 = 'JWT token BEFORE-VALID';
	public const COD23 = 'JWT token EXPIRED';
	// SESSION

	// XSRF
	public const COD50 = 'XSRF token INVALID';

	// 2FA
	public const COD60 = '2FA code INVALID';

	// common
	public const COD101 = 'AUTH required: Unauthorized';
	public const COD102 = 'XSRF-TOKEN required: Unauthorized';
	public const COD103 = 'Exception initializing user data';
}
