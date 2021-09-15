<?php
namespace renovant\core\auth;
use renovant\core\Exception;
class AuthException extends Exception {
	// COOKIE

	// JWT
	const COD21 = 'JWT token INVALID';
	const COD22 = 'JWT token BEFORE-VALID';
	const COD23 = 'JWT token EXPIRED';
	// SESSION

	// XSRF
	const COD50 = 'XSRF token INVALID';
	// common
	const COD101 = 'AUTH required: Unauthorized';
	const COD102 = 'XSRF-TOKEN required: Unauthorized';
	const COD103 = 'Exception initializing user data';
}
