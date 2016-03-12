<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\acl;
/**
 * ACL Exception
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Exception extends \metadigit\core\Exception {
	// ACTIONS
	const COD100 = '[ACTION] "%s" DENIED';
	// FILTERS
	const COD200 = '[FILTER] "%s" value MISSING';
	const COD201 = '[FILTER] "%s" QUERY %s KO';
}
