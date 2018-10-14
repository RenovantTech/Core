<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\util\html;
/**
 * HTML Exception
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
class HtmlException extends \renovant\core\Exception {
	const COD1 = 'HtmlWriter - can not find template: %s';
	const COD2 = 'HtmlWriter - template run exception: %s';
	const COD3 = 'HtmlWriter - can not write file: %s';

}
