<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\auth;
use renovant\core\Exception as CoreException;
/**
 * AUTH module Exception
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
class Exception extends CoreException {
	// constructor
	const COD1 = 'AUTH - auth module "%s" invalid, must be one of %s';
	const COD12 = 'AUTH [JWT] - Firebase\JWT must be installed';
	// runtime
	const COD23 = 'AUTH [SESSION] - SESSION must be already started before AUTH->init()';
}
