<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\http;
/**
 * HTTP Session Exception.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class SessionException extends \metadigit\core\Exception {
	// Session configuration
	const COD1 = 'PHP configuration ERROR - please set session.auto_start 0 in your php.ini';
	const COD2 = 'Session config ERROR - "class" invalid value: <b>%s</b> - must be an existing class implementing one of the following: default, db, filesystem, sqlite';
	// Session starting
	const COD11 = 'SessionManager->start(): session has already been started by session.auto-start or session_start()';
	const COD12 = 'SessionManager->start(): session must be started before any output has been sent to the browser; output started in %s:%s';
	const COD13 = 'SessionManager->start(): FAILED to connect to session storage';
	// Session namespace
	const COD51 = 'Session must be started by SessionManager->start() before invoking new Session().';
	const COD52 = 'Invalid namespace "%s": must be a non-empty string, beginning with a letter.';
	const COD53 = 'Session namespace "%s" already stared!';
	// Session locking
	const COD61 = 'Session namespace "%s", LOCK violation!';
}
