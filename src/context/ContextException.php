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
	const COD1 = '{1}: object OID "{2}" is NOT defined';
	const COD2 = '{1}: object OID "{2}" NOT implementing required class/interface {2}';
	// configuration
	const COD11 = '{1}: XML config file NOT FOUND in path {2}';
	const COD12 = 'Context: invalid XML configuration, XSD not validated: {1}';
	const COD13 = '{1}: invalid context namespace in XML: namespace={2}';
	const COD14 = '{1}: invalid object ID namespace in XML: <object id="{2}">, must be inside namespace "{3}"';
	const COD15 = '{1}: invalid object constructor reference: <arg name="{2}" type="object">{3}</arg>, must be inside available namespaces: {4}';
	const COD16 = '{1}: invalid object property reference: <property name="{2}" type="object">{3}</property>, must be inside available namespaces: {4}';
}