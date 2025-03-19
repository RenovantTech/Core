<?php
namespace renovant\core\util;

class Date extends \DateTime {
	public function isLastOfMonth(): bool {
		$tomorrow = (clone $this)->modify('+1 days');
		return $this->format('m') < $tomorrow->format('m') || $this->format('Y') < $tomorrow->format('Y');
	}

	public function isToday(): bool {
		return $this->format('Y-m-d') == date('Y-m-d');
	}

	public function sformat($format) {
		return strftime($format, strtotime($this->format('Y-m-d H:i:s')));
	}

	public function __toString() {
		return $this->format('Y-m-d');
	}
}
