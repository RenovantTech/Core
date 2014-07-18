<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\util\xml;

class XMLException extends \metadigit\core\Exception {
	const COD1 = 'Validation with DTD Schema failed';
	const COD2 = 'Validation with XML Schema failed.';
	const COD3 = 'Validation with RelaxNG Schema failed';

	function getInfo() {
		$r = '<br/><b>Errors:</b>'.self::asHtml($this->args[1]).'<br/>'.
				'-----------------------------<br/>'.
				'<b>XML:</b><br/>'.
				self::asHtml($this->args[2]).'<br/>'.
				'-----------------------------<br/>';
		switch ($this->code) {
			case 1:
				$r .= '<b>DTD:</b><br/>'.self::asHtml($this->args[3]);
			break;
			case 2:
				$r .= '<b>XML Schema:</b><br/>'.self::asHtml($this->args[3]);
			break;
			case 3:
				$r .= '<b>RelaxNG Schema:</b><br/>'.self::asHtml($this->args[3]);
			break;
		}
		$r .= '';
		return $r;
	}

	static function asHtml($string) {
		return str_replace(["\t","\n"], ['&nbsp;&nbsp;&nbsp;&nbsp;','<br/>'], htmlentities($string));
	}
}