<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\container;
/**
 * ContainerException
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
class ContainerException extends \renovant\core\Exception {
	// runtime
	const COD1 = '%s: object ID "%s" NOT defined';
	const COD2 = '%s: object OID "%2$s" NOT implementing required class/interface %2$s';
	const COD4 = 'CoreProxy `%s`: can not retrieve proxied object';
	// configuration
	const COD11 = '%s: namespace %s - YAML config file NOT FOUND';
	const COD12 = '%s: namespace %s - invalid YAML configuration';
}
