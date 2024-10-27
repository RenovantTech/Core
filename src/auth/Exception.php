<?php
namespace renovant\core\auth;

use renovant\core\Exception as CoreException;

class Exception extends CoreException {
	// constructor
	public const COD12 = 'AUTH [JWT] - Firebase\JWT must be installed';
	// runtime
	public const COD23 = 'AUTH [SESSION] - SESSION must be already started before AUTH->init()';
}
