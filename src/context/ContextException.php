<?php
namespace renovant\core\context;
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
