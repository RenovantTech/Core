<?php
namespace renovant\core\util\graph;
use const renovant\core\trace\T_INFO;
use renovant\core\sys,
	renovant\core\db\Query,
	renovant\core\util\DatePeriod;
class ChartJs {
	use \renovant\core\CoreTrait;

	const INTERVAL_DAY = 'DAY';
	const INTERVAL_MONTH = 'MONTH';

	protected Query $Query;

	function __construct(string $table, ?string $fields=null, ?string $pdo=null) {
		$this->Query = new Query($table, $fields, $pdo);
	}

	function fetch(string $criteria, array $params, string $interval, string $from, string $to, array $dataFn): array {
		$prevTraceFn = sys::traceFn(__CLASS__.'->fetch');
		try {
			list($data, $labels) = $this->_init($interval, $from, $to, count($dataFn));
			$raw = $this->_fetch($criteria, $params, $interval, $from, $to);
			sys::trace(LOG_DEBUG, T_INFO, 'calculate data');
			$sets = count($dataFn);
			switch ($interval) {
				case self::INTERVAL_DAY:
					foreach ($raw as $v) {
						for ($i = 0; $i < $sets; $i++)
							$data[$i][$v['date']] = $dataFn[$i]($v);
					}
					break;
				case self::INTERVAL_MONTH:
					foreach ($raw as $v) {
						for ($i = 0; $i < $sets; $i++)
							$data[$i][$v['year'].str_pad($v['month'], 2, '0', STR_PAD_LEFT)] = $dataFn[$i]($v);
					}
					break;
			}
			$return = [];
			for ($i = 0; $i < $sets; $i++)
				$return[] = array_values($data[$i]);
			return [$return, $labels];
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	protected function _fetch(string &$criteria, array &$params, string $interval, string $from, string $to): array {
		sys::trace(LOG_DEBUG, T_INFO, 'fetch data');
		$fromYear = (int) substr($from, 0, 4);
		$toYear = (int) substr($to, 0, 4);
		$fromMonth = (int) substr($from, 5, 2);
		$toMonth = (int) substr($to, 5, 2);

		switch ($interval) {
			case self::INTERVAL_DAY:
				$orderBy = 'date ASC';
				$criteria .= ' AND date >= :from AND date <= :to';
				$params['from'] = $from;
				$params['to'] = $to;
				break;
			case self::INTERVAL_MONTH:
				$orderBy = 'year ASC, month ASC';
				if ($fromYear == $toYear) {
					$criteria .= ' AND year = :year AND month >= :fromMonth AND month <= :toMonth';
					$params['year'] = $fromYear;

				} else {
					$criteria .= ' AND ( ';
					$criteria .= ' (year = :fromYear AND month >= :fromMonth)';
					if ($toYear > $fromYear + 1)
						$criteria .= ' OR year IN (' . implode(',', range($fromYear + 1, $toYear - 1)) . ')';
					$criteria .= ' OR (year = :toYear AND month <= :toMonth)';
					$criteria .= ' )';
					$params['fromYear'] = $fromYear;
					$params['toYear'] = $toYear;
				}
				$params['fromMonth'] = $fromMonth;
				$params['toMonth'] = $toMonth;
				break;
		}
		return $this->Query
			->criteria($criteria)
			->orderBy($orderBy)
			->execSelect($params)->fetchAll(\PDO::FETCH_ASSOC);
	}

	protected function _init(string $interval, string $from, string $to, int $sets): array {
		sys::trace(LOG_DEBUG, T_INFO, 'init data & labels');

		$data = [];
		$labels = [];
		$fromYear = (int) substr($from, 0, 4);
		$toYear = (int) substr($to, 0, 4);
		$fromMonth = (int) substr($from, 5, 2);
		$toMonth = (int) substr($to, 5, 2);
		$curYear = $fromYear;
		$curMonth = $fromMonth;

		switch ($interval) {
			case self::INTERVAL_DAY:
				$days = DatePeriod::create($from, 'P1D', $to);
				foreach ($days as $day) {
					for ($i = 0; $i < $sets; $i++)
						$data[$i][$day->format('Y-m-d')] = null;
					$labels[] = $day->format('Y-m-d');
				}
				break;
			case self::INTERVAL_MONTH:
				while (true) {
					for ($i = 0; $i < $sets; $i++)
						$data[$i][$curYear . str_pad($curMonth, 2, '0', STR_PAD_LEFT)] = null;
					$labels[] = $curYear . '/' . str_pad($curMonth, 2, '0', STR_PAD_LEFT);
					if ($curYear == $toYear && $curMonth == $toMonth)
						break;
					if ($curMonth < 12)
						$curMonth++;
					else {
						$curYear++;
						$curMonth = 1;
					}
				}
				break;
		}
		return [$data, $labels];
	}
}
