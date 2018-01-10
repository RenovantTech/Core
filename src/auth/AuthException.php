<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\auth;
use metadigit\core\Exception;
/**
 * 401 Unauthorized
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class AuthException extends Exception {
	// COOKIE

	// JWT
	const COD21 = 'AUTH [JWT] - JWT token INVALID';
	// SESSION

	// XSRF
	const COD50 = 'AUTH [%s] - XSRF token INVALID';
	// common
	const COD101 = 'AUTH [%s] - AUTH required: Unauthorized';
	const COD102 = 'AUTH [%s] - XSRF-TOKEN required: Unauthorized';
}
