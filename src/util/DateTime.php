<?php
namespace renovant\core\util;
/**
 * DateTime: extends base php DataTime class.
 */
class DateTime extends \DateTime {

	function sformat($format) {
		return strftime($format, strtotime($this->format('Y-m-d H:i:s')));
	}

	function __toString() {
		return $this->format('Y-m-d H:i:s');
	}
}
