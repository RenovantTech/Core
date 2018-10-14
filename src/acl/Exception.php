<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\acl;
/**
 * ACL Exception
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
class Exception extends \renovant\core\Exception {
	// ACTIONS
	const COD100 = '[ACTION] "%s" DENIED';
	// FILTERS
	const COD200 = '[FILTER] "%s" value MISSING';
	const COD201 = '[FILTER] "%s" QUERY %s KO';
}
