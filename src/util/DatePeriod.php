<?php
namespace renovant\core\util;

class DatePeriod extends \DatePeriod {

	/**
	 * @throws \Exception
	 */
	static function new($start, $interval, $end): DatePeriod {
		if(is_string($start)) $start = new \DateTime($start);
		if(is_string($interval)) $interval = new \DateInterval($interval);
		if(is_string($end)) $end = (new \DateTime($end))->modify('+1 days');

		return new DatePeriod($start, $interval, $end);
	}
}
