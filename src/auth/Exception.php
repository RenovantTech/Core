<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace metadigit\core\auth;
use metadigit\core\Exception as CoreException;
/**
 * AUTH module Exception
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Exception extends CoreException {
	// constructor
	const COD1 = 'AUTH - auth module "%s" invalid, must be one of %s';
	const COD12 = 'AUTH [JWT] - Firebase\JWT must be installed';
	// runtime
	const COD23 = 'AUTH [SESSION] - SESSION must be already started before AUTH->init()';
}
