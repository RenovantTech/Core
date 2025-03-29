<?php
namespace renovant\core\container;

class ContainerException extends \renovant\core\Exception {
	// runtime
	public const COD1 = '%s: object ID "%s" NOT defined';
	public const COD2 = '%s: object OID "%2$s" NOT implementing required class/interface %2$s';
	public const COD4 = 'CoreProxy `%s`: can not retrieve proxied object';
	// configuration
	public const COD11 = '%s: context %s - YAML config file NOT FOUND';
	public const COD12 = '%s: context %s - invalid YAML configuration';
}
