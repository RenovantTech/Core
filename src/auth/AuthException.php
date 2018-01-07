<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\auth;
use metadigit\core\Exception as BaseException;
/**
 * AUTH Exception
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class AuthException extends BaseException {
	// constructor
	const COD1 = 'AUTH - auth module "%s" invalid, must be one of %s';
	// init
	const COD13 = 'AUTH - SESSION must be already started before AUTH->init()';
}
