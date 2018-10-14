<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\context;
/**
 * ContextException
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
class ContextException extends \renovant\core\Exception {
	// runtime Container
	const COD1 = '%s: object OID "%s" is NOT defined';
	const COD2 = '%1$s: object OID "%2$s" NOT implementing required class/interface %2$s';
	// configuration
	const COD11 = '%s: namespace %s - YAML config file NOT FOUND';
	const COD12 = '%s: namespace %s - invalid YAML configuration';
	const COD14 = '%s: invalid object ID namespace in YAML: "%s" must be inside namespace "%s"';
	const COD15 = '%s: invalid object constructor reference: "%s: %s" must be inside available namespaces: %s';
	const COD16 = '%s: invalid object property reference: "%s: %s" must be inside available namespaces: %s';
}
