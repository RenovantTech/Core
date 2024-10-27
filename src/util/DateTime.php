<?php
namespace renovant\core\util;

class DateTime extends \DateTime {
	public function sformat($format) {
		return strftime($format, strtotime($this->format('Y-m-d H:i:s')));
	}

	public function __toString() {
		return $this->format('Y-m-d H:i:s');
	}
}
