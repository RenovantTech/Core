<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\context;
/**
 * ContextException
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class ContextException extends \metadigit\core\Exception {
	// runtime Container
	const COD1 = '%s: object OID "%s" is NOT defined';
	const COD2 = '%1$s: object OID "%2$s" NOT implementing required class/interface %2$s';
	// configuration
	const COD11 = '%s: YAML config file NOT FOUND in path %s';
	const COD12 = 'Context: invalid YAML configuration, YAML not validated: %s';
	const COD14 = '%s: invalid object ID namespace in YAML: "%s" must be inside namespace "%s"';
	const COD15 = '%s: invalid object constructor reference: "%s: %s" must be inside available namespaces: %s';
	const COD16 = '%s: invalid object property reference: "%s: %s" must be inside available namespaces: %s';
}
