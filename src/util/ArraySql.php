<?php
namespace renovant\core\util;
/**
 * Array query language
 */
class ArraySql {

	protected $data;

	function __construct(&$data) {
		$this->data =& $data;
	}

	function criteriaExp($criteriaExp=null) {
		if(empty($criteriaExp)) return $this;
		$expArray = explode('|', $criteriaExp);
		foreach ($expArray as $cExp) {
			$cExpTokens = explode(',', $cExp);
			list($field, $op) = $cExpTokens;
			$values = array_slice($cExpTokens, 2);
			switch($op) {
				case 'EQ':
					$this->data = array_values(array_filter($this->data, function ($data) use ($field, $values) {
						return ($data[$field] == $values[0]);
					}, ARRAY_FILTER_USE_BOTH));
					break;
				case '!EQ':
					$this->data = array_values(array_filter($this->data, function ($data) use ($field, $values) {
						return ($data[$field] != $values[0]);
					}, ARRAY_FILTER_USE_BOTH));
					break;
				case 'LIKEHAS':
					$this->data = array_values(array_filter($this->data, function ($data) use ($field, $values) {
						return (strpos($data[$field], $values[0]) !== false);
					}, ARRAY_FILTER_USE_BOTH));
					break;
				case '!LIKEHAS':
					$this->data = array_values(array_filter($this->data, function ($data) use ($field, $values) {
						return (strpos($data[$field], $values[0]) === false);
					}, ARRAY_FILTER_USE_BOTH));
					break;
				default:
					trigger_error(__METHOD__.' - invalid criteriaExp: '.$cExp, E_USER_ERROR);
			}
		}
		return $this;
	}

	function orderExp($orderExp=null) {
		if(empty($orderExp)) return $this;
		$expArray = explode('|', $orderExp);
		$sorts = $indexes = [];
		foreach ($expArray as $exp) {
			list($col, $sort) = explode('.', $exp);
			$sorts[] = (strtoupper($sort) == 'ASC') ? SORT_ASC : SORT_DESC;
			$indexes[]  = array_column($this->data, $col);
		}
		switch (count($expArray)) {
			case 1: array_multisort($indexes[0], $sorts[0], $this->data); break;
			case 2: array_multisort($indexes[0], $sorts[0], $indexes[1], $sorts[1], $this->data); break;
			case 3: array_multisort($indexes[0], $sorts[0], $indexes[1], $sorts[1], $indexes[2], $sorts[2], $this->data); break;
			case 4: array_multisort($indexes[0], $sorts[0], $indexes[1], $sorts[1], $indexes[2], $sorts[2], $indexes[3], $sorts[3], $this->data); break;
		}
		return $this;
	}

	function page($page=null, $pageSize=null) {
		array_slice($this->data, ($page-1)*$pageSize, $pageSize);
		return $this;
	}
}
