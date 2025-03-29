<?php
namespace renovant\core\context;

class ContextException extends \renovant\core\Exception {
	// runtime Container
	public const COD1 = '%s: object OID "%s" is NOT defined';
	public const COD2 = '%1$s: object OID "%2$s" NOT implementing required class/interface %2$s';
	// configuration
	public const COD11 = '%s: context %s - YAML config file NOT FOUND';
	public const COD12 = '%s: context %s - invalid YAML configuration';
	public const COD14 = '%s: invalid object ID context in YAML: "%s" must be inside context "%s"';
	public const COD15 = '%s: invalid object constructor reference: "%s: %s" must be inside available contexts: %s';
	public const COD16 = '%s: invalid object property reference: "%s: %s" must be inside available contexts: %s';
}
