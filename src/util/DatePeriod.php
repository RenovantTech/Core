<?php
namespace renovant\core\util;

class DatePeriod extends \DatePeriod {

	/**  @throws \Exception */
	static function create($start, $interval, $end): array {
		if(is_string($start)) $start = new \DateTime($start);
		if(is_string($interval)) $interval = new \DateInterval($interval);
		if(is_string($end)) $end = (new \DateTime($end))->modify('+1 days');
		$Period = new \DatePeriod($start, $interval, $end);
		$dates = [];
		foreach ($Period as $date)
			$dates[] = new Date($date->format('Y-m-d'));
		return $dates;
	}
}
