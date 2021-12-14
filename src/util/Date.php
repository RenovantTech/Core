<?php
namespace renovant\core\util;

class Date extends \DateTime {

	function isLastOfMonth(): bool {
		$tomorrow = (clone $this)->modify('+1 days');
		return $this->format('m') < $tomorrow->format('m') || $this->format('Y') < $tomorrow->format('Y');
	}

	function isToday(): bool {
		return $this->format('Y-m-d') == date('Y-m-d');
	}

	function sformat($format) {
		return strftime($format, strtotime($this->format('Y-m-d H:i:s')));
	}

	function __toString() {
		return $this->format('Y-m-d');
	}
}
