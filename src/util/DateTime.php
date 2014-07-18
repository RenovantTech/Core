<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\util;
/**
 * DateTime: extends base php DataTime class.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class DateTime extends \DateTime {

	function sformat($format) {
		return strftime($format, strtotime($this->format('Y-m-d H:i:s')));
	}

	function __toString() {
		return $this->format('Y-m-d H:i:s');
	}
}