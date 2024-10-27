<?php
namespace renovant\core\util\xml;

class XMLException extends \renovant\core\Exception {
	public const COD1 = 'Validation with DTD Schema failed';
	public const COD2 = 'Validation with XML Schema failed.';
	public const COD3 = 'Validation with RelaxNG Schema failed';

	public function getInfo() {
		$r = '<br/><b>Errors:</b>' . self::asHtml($this->args[1]) . '<br/>' .
				'-----------------------------<br/>' .
				'<b>XML:</b><br/>' .
				self::asHtml($this->args[2]) . '<br/>' .
				'-----------------------------<br/>';
		switch ($this->code) {
			case 1:
				$r .= '<b>DTD:</b><br/>' . self::asHtml($this->args[3]);
				break;
			case 2:
				$r .= '<b>XML Schema:</b><br/>' . self::asHtml($this->args[3]);
				break;
			case 3:
				$r .= '<b>RelaxNG Schema:</b><br/>' . self::asHtml($this->args[3]);
				break;
		}
		$r .= '';
		return $r;
	}

	public static function asHtml($string) {
		return str_replace(["\t", "\n"], ['&nbsp;&nbsp;&nbsp;&nbsp;', '<br/>'], htmlentities($string));
	}
}
