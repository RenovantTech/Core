<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\util\xml;

libxml_use_internal_errors(true);
abstract class XMLValidator {

	static function relaxNG($xml, $rng) {
		$dom = new \DOMDocument();
		$dom->loadXML($xml);
		if(!$dom->relaxNGValidate($rng)) throw new XMLException(3, [self::errors(), $xml, $rng]);
		return true;
	}

	/**
	 * Validate a XML based on a RelaxNG schema, both passed as source (=strings).
	 * @param string $xml the XML
	 * @param string $rng the RNG
	 * @return boolean TRUE on successful validation
	 * @throws XMLException
	 */
	static function relaxNGSource($xml, $rng) {
		$dom = new \DOMDocument();
		$dom->loadXML($xml);
		if(!$dom->relaxNGValidateSource($rng)) throw new XMLException(3, [self::errors(), $xml, $rng]);
		return true;
	}

	/**
	 * Validate a XML file based on a XML Schema
	 * @param string $xml the xml file path
	 * @param string $xsd the xsd file path
	 * @throws XMLException
	 * @return boolean TRUE on successful validation
	 */
	static function schema($xml, $xsd) {
		$dom = new \DOMDocument();
		$dom->load($xml);
		if(!$dom->schemaValidate($xsd)) throw new XMLException(2, [self::errors(), $xml, $xsd]);
		return true;
	}

	/**
	 * Validate a XML based on a XML schema, both passed as source (=strings).
	 * @param string $xml the XML
	 * @param string $xsd the XSD
	 * @return boolean TRUE on successful validation
	 * @throws XMLException
	 */
	static function schemaSource($xml, $xsd) {
		$dom = new \DOMDocument();
		$dom->loadXML($xml);
		if(!$dom->schemaValidateSource($xsd)) throw new XMLException(2, [self::errors(), $xml, $xsd]);
		return true;
	}

	// implementation methods -------------------------------------------------------------------------------------

	static private function errors() {
		$errors = libxml_get_errors();
		$r = '';
		foreach($errors as $e) {
			$r .= self::error($e);
		}
		libxml_clear_errors();
		return $r;
	}

	static private function error($e) {
		$r = "\n";
		$r .= "#[$e->line:$e->column] ";
		switch($e->level) {
			case LIBXML_ERR_WARNING:
				$r .= "Warning $e->code - ";
				break;
			case LIBXML_ERR_ERROR:
				$r .= "Error $e->code - ";
				break;
			case LIBXML_ERR_FATAL:
				$r .= "Fatal Error $e->code -";
				break;
		}
		$r .= trim($e->message);
		//if($e->file)$r.=" in $e->file";
		return $r;
	}
}
