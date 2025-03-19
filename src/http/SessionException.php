<?php
namespace renovant\core\http;

class SessionException extends \renovant\core\Exception {
	// Session configuration
	public const COD1 = 'PHP configuration ERROR - please set session.auto_start 0 in your php.ini';
	public const COD2 = 'Session config ERROR - "class" invalid value: <b>%s</b> - must be an existing class implementing one of the following: default, db, filesystem, sqlite';
	// Session starting
	public const COD11 = 'SessionManager->start(): session has already been started by session.auto-start or session_start()';
	public const COD12 = 'SessionManager->start(): session must be started before any output has been sent to the browser; output started in %s:%s';
	public const COD13 = 'SessionManager->start(): FAILED to connect to session storage';
	// Session namespace
	public const COD51 = 'Session must be started by SessionManager->start() before invoking new Session().';
	public const COD52 = 'Invalid namespace "%s": must be a non-empty string, beginning with a letter.';
	public const COD53 = 'Session namespace "%s" already stared!';
	// Session locking
	public const COD61 = 'Session namespace "%s", LOCK violation!';
}
