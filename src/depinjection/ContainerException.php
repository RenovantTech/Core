<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\depinjection;
/**
 * ContainerException
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class ContainerException extends \metadigit\core\Exception {
	// runtime
	const COD1 = '{1}: object OID "{2}" is NOT defined';
	const COD2 = '{1}: object OID "{2}" NOT implementing required class/interface {2}';
	const COD4 = 'ObjectProxy `{1}`: can not retrieve proxied object';
	// configuration
	const COD11 = '{1}: XML config file NOT FOUND in path {2}';
	const COD12 = 'DI Container: invalid XML configuration, XSD not validated: {1}';
}