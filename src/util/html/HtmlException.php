<?php
namespace renovant\core\util\html;

class HtmlException extends \renovant\core\Exception {
	public const COD1 = 'HtmlWriter - can not find template: %s';
	public const COD2 = 'HtmlWriter - template run exception: %s';
	public const COD3 = 'HtmlWriter - can not write file: %s';
}
