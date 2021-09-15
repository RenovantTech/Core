<?php
namespace renovant\core\container;
class ContainerException extends \renovant\core\Exception {
	// runtime
	const COD1 = '%s: object ID "%s" NOT defined';
	const COD2 = '%s: object OID "%2$s" NOT implementing required class/interface %2$s';
	const COD4 = 'CoreProxy `%s`: can not retrieve proxied object';
	// configuration
	const COD11 = '%s: namespace %s - YAML config file NOT FOUND';
	const COD12 = '%s: namespace %s - invalid YAML configuration';
}
