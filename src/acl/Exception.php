<?php
namespace renovant\core\acl;
class Exception extends \renovant\core\Exception {
	// ACTIONS
	const COD100 = '[ACTION] "%s" DENIED';
	// FILTERS
	const COD200 = '[FILTER] "%s" value MISSING';
	const COD201 = '[FILTER] "%s" QUERY %s KO';
}
