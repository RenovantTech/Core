<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\util\html;
/**
 * HTML Exception
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class HtmlException extends \metadigit\core\Exception {
	const COD1 = 'HtmlWriter - can not find template: %s';
	const COD2 = 'HtmlWriter - template run exception: %s';
	const COD3 = 'HtmlWriter - can not write file: %s';

}
