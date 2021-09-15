<?php
namespace renovant\core\auth;
use renovant\core\Exception as CoreException;
class Exception extends CoreException {
	// constructor
	const COD1 = 'AUTH - auth module "%s" invalid, must be one of %s';
	const COD12 = 'AUTH [JWT] - Firebase\JWT must be installed';
	// runtime
	const COD23 = 'AUTH [SESSION] - SESSION must be already started before AUTH->init()';
}
