<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\util\xml;
/**
 * XMLHelper
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class XMLHelper {
	/**
	 * Merge 2 XML together.
	 * @param string $xml1
	 * @param string $xml2
	 * @param string $xpath1 XML1 starting merging node (only the first match will be considered)
	 * @param string $xpath2 XML2 nodes to be merged
	 * @return string merged XML
	 */
	static function merge($xml1, $xml2, $xpath1='/*', $xpath2='/*/*') {
		// convert XML strings into DOM objectes
		$DOM1 = new \DomDocument(); $DOM1->loadXML($xml1);
		$DOM2 = new \DomDocument(); $DOM2->loadXML($xml2);
		// pull all child elements of XML2
		$domXPath = new \domXPath($DOM2);
		$xQuery2 = $domXPath->query($xpath2);
		$domXPath = new \domXPath($DOM1);
		$xQuery1 = $domXPath->query($xpath1);
		for($i=0; $i<$xQuery2->length; $i++) {
			// and pump them into XML1
			$xQuery1->item(0)->appendChild($DOM1->importNode($xQuery2->item($i),true));
		}
		return $DOM1->saveXML();
	}
}