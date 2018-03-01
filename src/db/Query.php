<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\db;
use metadigit\core\sys;
/**
 * Query
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Query {
	use \metadigit\core\CoreTrait;

	const EXP_DELIMITER = '|';
	/** SQL criteria
	 * @var array */
	protected $criteriaSql = [];
	/** SQL criteria exp
	 * @var array */
	protected $criteriaExp = [];
	/** Criteria Expression dictionary
	 * @var array */
	protected $dictionary = [];
	/** SQL fields (for SELECT, INSERT, UPDATE)
	 * @var string */
	protected $fields;
	/** PDO instance ID
	 * @var string */
	protected $pdo;
	/** PDOStatement
	 * @var \PDOStatement */
	protected $PDOStatement;
	/** PDO params
	 * @var array */
	protected $params = [];
	/** SQL target (table or tables join, aka the FROM/INTO clause)
	 * @var string */
	protected $target;
	/** SQL GROUP BY
	 * @var string */
	protected $groupBy;
	/** SQL HAVING
	 * @var string */
	protected $having;
	/** SQL OFFSET
	 * @var string */
	protected $offset;
	/** SQL ORDER BY
	 * @var string */
	protected $orderBy;
	/** SQL LIMIT
	 * @var string */
	protected $limit;
	/** SQL WITH ROLLUP
	 * @var boolean */
	protected $withRollup = false;

	/**
	 * Create new Query object
	 * @param string $pdo optional PDO instance ID, default to 'master'
	 */
	function __construct($pdo='master') {
		$this->pdo = $pdo;
	}

	/**
	 * Set Query target: table, view, procedure
	 * @param string $target SQL target
	 * @param string|null $fields SQL fields (for SELECT, INSERT, UPDATE)
	 * @return $this
	 */
	function on($target, $fields=null) {
		$this->PDOStatement = null;
		$this->target = $target;
		$this->fields = $fields;
		return $this;
	}

	/**
	 * Execute CALL statement
	 * @param array $params
	 * @return array|true|null output params if any
	 */
	function execCall(array $params) {
		$sql = '';
		$outputParams = [];
		$fields = explode(',',str_replace(' ','',$this->fields));
		foreach($fields as $k=>$v){
			$k++;
			if($v[0]!='@') {
				$sql .= ', :'.$v.'_'.$k;
				$this->params[$v.'_'.$k] = ':'.$v;
			} else {
				$sql .= ', '.$v;
				$outputParams[] = $v;
			}
		}
		$sql = sprintf('CALL %s(%s)', $this->target, substr($sql,2));
		$this->doExec($sql, $params)->rowCount();
		if(empty($outputParams)) return true;
		else {
			$keys = [];
			$sql = sprintf('SELECT %s', implode(', ', $outputParams));
			foreach($outputParams as $p) $keys[] = substr($p,1);
			return array_combine($keys, sys::pdo($this->pdo)->query($sql)->fetch(\PDO::FETCH_NUM));
		}
	}

	/**
	 * Execute COUNT statement
	 * @param array $params PDO params
	 * @return integer count result
	 */
	function execCount(array $params=[]) {
		$sql = sprintf('SELECT COUNT(*) FROM `%s` %s', $this->target, $this->parseCriteria());
		return (int) $this->doExec($sql, $params)->fetchColumn();
	}

	/**
	 * Execute DELETE statement
	 * @param array $params PDO params
	 * @return int n° of deleted rows
	 */
	function execDelete(array $params=[]) {
		$sql = sprintf('DELETE FROM `%s` %s', $this->target, $this->parseCriteria());
		if(!empty($this->orderBy)) $sql .= ' ORDER BY '.$this->orderBy;
		if(!empty($this->limit)) $sql .= ' LIMIT '.$this->limit;
		return (int) $this->doExec($sql, $params)->rowCount();
	}

	/**
	 * Execute INSERT statement
	 * @param array $params PDO params
	 * @return int n° of inserted rows
	 */
	function execInsert(array $params) {
		$sql1 = $sql2 = '';
		$fields = explode(',',str_replace(' ','',$this->fields));
		foreach($fields as $k=>$v) {
			$k++;
			$sql1 .= ', `'.$v.'`';
			$sql2 .= ', :'.$v.'_'.$k;
			$this->params[$v.'_'.$k] = ':'.$v;
		}
		$sql = sprintf('INSERT INTO `%s` (%s) VALUES (%s)', $this->target, substr($sql1,1), substr($sql2,1));
		return (int) $this->doExec($sql, $params)->rowCount();
	}

	/**
	 * Execute INSERT .. ON DUPLICATE KEY UPDATE statement
	 * @param array $params PDO params
	 * @param array $keys table PRIMARY/UNIQUE keys
	 * @return int n° of inserted rows
	 */
	function execInsertUpdate(array $params, array $keys) {
		$sql1 = $sql2 = $sql3 = '';
		$fields = explode(',',str_replace(' ','',$this->fields));
		foreach($fields as $k=>$v) {
			$k++;
			$sql1 .= ', `'.$v.'`';
			$sql2 .= ', :'.$v.'_'.$k;
			$this->params[$v.'_'.$k] = ':'.$v;
			if(!in_array($v, $keys)) {
				$sql3 .= ', '.$v.' = :'.$v.'__'.$k;
				$this->params[$v.'__'.$k] = ':'.$v;
			}
		}
		$sql = sprintf('INSERT INTO `%s` (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s', $this->target, substr($sql1,1), substr($sql2,1), substr($sql3,1));
		return (int) $this->doExec($sql, $params)->rowCount();
	}

	/**
	 * Execute SELECT statement
	 * @param array $params PDO params
	 * @return \PDOStatement
	 */
	function execSelect(array $params=[]) {
		$sql = sprintf('SELECT %s FROM `%s` %s', ($this->fields?:'*'), $this->target, $this->parseCriteria());
		if(!empty($this->groupBy)) {
			$sql .= ' GROUP BY '.$this->groupBy;
			if($this->withRollup) $sql .= ' WITH ROLLUP ';
		}
		if(!empty($this->having)) $sql .= ' HAVING '.$this->having;
		if(!empty($this->orderBy)) $sql .= ' ORDER BY '.$this->orderBy;
		if(!empty($this->limit)) $sql .= ' LIMIT '.$this->limit;
		if(!empty($this->offset)) $sql .= ' OFFSET '.$this->offset;
		return $this->doExec($sql, $params);
	}

	/**
	 * Execute UPDATE statement
	 * @param array $params
	 * @return int n° of rows deleted
	 */
	function execUpdate(array $params) {
		$sql = '';
		$fields = explode(',',str_replace(' ','',$this->fields));
		foreach($fields as $k=>$v){
			$k++;
			$sql .= ', `'.$v.'` = :'.$v.'_'.$k;
			$this->params[$v.'_'.$k] = ':'.$v;
		}
		$sql = sprintf('UPDATE `%s` SET %s %s', $this->target, substr($sql,2), $this->parseCriteria());
		return (int) $this->doExec($sql, $params)->rowCount();
	}

	function errorCode() {
		return $this->PDOStatement->errorCode();
	}

	/**
	 * Add Criteria
	 * @param string $criteria
	 * @return $this
	 */
	function criteria($criteria) {
		if(!empty($criteria)) $this->criteriaSql[] = $criteria;
		return $this;
	}

	/**
	 * Add Criteria Expression
	 * @param string $criteriaExp
	 * @return $this
	 */
	function criteriaExp($criteriaExp) {
		if(!empty($criteriaExp)) $this->criteriaExp = array_merge($this->criteriaExp, explode(self::EXP_DELIMITER, $criteriaExp));
		return $this;
	}

	/**
	 * Set GROUP BY
	 * @param string $groupBy
	 * @return $this
	 */
	function groupBy($groupBy) {
		$this->PDOStatement = null;
		$this->groupBy = $groupBy;
		return $this;
	}

	/**
	 * Set HAVING
	 * @param string $having
	 * @return $this
	 */
	function having($having) {
		$this->PDOStatement = null;
		$this->having = $having;
		return $this;
	}

	/**
	 * Set LIMIT
	 * @param integer $limit
	 * @return $this
	 */
	function limit($limit) {
		$this->PDOStatement = null;
		$this->limit = (int)$limit;
		return $this;
	}

	/**
	 * Set OFFSET
	 * @param integer $offset
	 * @return $this
	 */
	function offset($offset) {
		$this->PDOStatement = null;
		$this->offset = (int)$offset;
		return $this;
	}

	/**
	 * Set ORDER BY
	 * @param string $orderBy
	 * @return $this
	 */
	function orderBy($orderBy) {
		$this->PDOStatement = null;
		$this->orderBy = $orderBy;
		return $this;
	}

	/**
	 * Set ORDER BY Expression
	 * @param string $orderByExp
	 * @return $this
	 */
	function orderByExp($orderByExp) {
		$this->PDOStatement = null;
		$expArray = explode(self::EXP_DELIMITER, $orderByExp);
		$orderBy = [];
		foreach($expArray as $oExp) {
			if(isset($this->dictionary['order-by'][str_replace('.','',$oExp)]))
				$oExp = $this->dictionary['order-by'][str_replace('.','',$oExp)];
			$orderBy[] = str_replace('.',' ',$oExp);
		}
		$this->orderBy = implode(', ',$orderBy);
		return $this;
	}

	/**
	 * Set WITH ROLLUP
	 * @return $this
	 */
	function withRollup() {
		$this->PDOStatement = null;
		$this->withRollup = true;
		return $this;
	}

	/**
	 * Set Criteria Expression Dictionary
	 * @param array $dictionary
	 * @return $this
	 */
	function setCriteriaDictionary(array $dictionary) {
		$this->dictionary['criteria'] = $dictionary;
		return $this;
	}

	/**
	 * Set OrderBy Expression Dictionary
	 * @param array $dictionary
	 * @return $this
	 */
	function setOrderByDictionary(array $dictionary) {
		$this->dictionary['order-by'] = $dictionary;
		return $this;
	}

	/**
	 * Execute query
	 * @param string $sql SQL statement
	 * @param array $params PDO params
	 * @return \PDOStatement
	 */
	protected function doExec($sql, array $params=[]) {
		$execParams = $this->params;
		foreach($params as $k=>$v) {
			if($keys = array_keys($execParams, ':'.$k, true)) {
				foreach($keys as $key) {
					$execParams[$key] = $v;
				}
			} else $execParams[$k] = $v;
		}
		if(is_null($this->PDOStatement)) $this->PDOStatement = sys::pdo($this->pdo)->prepare($sql);
		$this->PDOStatement->execute($execParams);
		return $this->PDOStatement;
	}

	protected function parseCriteria() {
		$i = 0;
		$sql = [];
		$params = [];
		$addParam = function($field, $value) use (&$i, &$params) {
			$params[$field.'_'.$i] = $value;
		};
		$addParams = function($field, $values) use (&$i, &$params) {
			foreach($values as $j=>$value) {
				$params[$field.'_'.$i.'_'.($j+1)] = $value;
			}
		};
		// #1 parse SQL criteria
		foreach($this->criteriaSql as $cSql) {
			if(preg_match_all('(:[\w]+)', $cSql, $matches)) {
				foreach($matches[0] as $match) {
					$params[substr($match,1)] = $match;
				}
			}
			$sql[] = $cSql;
		}
		// #2 add Expression Dictionary translations
		$transExp = [];
		foreach($this->criteriaExp as $k => $cExp) {
			$cExp = explode(',', $cExp);
			$expName = $cExp[0];
			$expValues = array_slice($cExp, 1);
			if(isset($this->dictionary['criteria'][$expName])) {
				$newExp = $this->dictionary['criteria'][$expName];
				unset($this->criteriaExp[$k]);
				$n = substr_count($newExp, '?');
				$search = $replace = [];
				if(preg_match('/([\w]+),([\!A-Z]+)([,:?\w]*)/', $newExp)) {
					for($j=1; $j<=$n; $j++) {
						$search[] = '?'.$j;
						$replace[] = $expValues[$j-1];
					}
					$transExp[] = str_replace($search, $replace, $newExp);
				} else {
					for($j=1; $j<=$n; $j++) {
						$i++;
						$addParam('_', $expValues[$j-1]);
						$search[] = '?'.$j;
						$replace[] = ':__'.$i;
					}
					$newExp = str_replace($search, $replace, $newExp);
					$sql[] = $newExp;
				}
			}
		}
		$this->criteriaExp(implode('|',$transExp));
		// #3 parse criteria expressions
		foreach($this->criteriaExp as $cExp) {
			$i++;
			$cExpTokens = explode(',', $cExp);
			list($field, $op) = $cExpTokens;
			$values = array_slice($cExpTokens, 2);
			switch($op) {
				case 'EQ':
					$sql[] = "`$field` = :${field}_$i"; $addParam($field, $values[0]);
					break;
				case '!EQ':
					$sql[] = "`$field` != :${field}_$i"; $addParam($field, $values[0]);
					break;
				case 'LT':
					$sql[] = "`$field` < :${field}_$i"; $addParam($field, $values[0]);
					break;
				case 'LTE':
					$sql[] = "`$field` <= :${field}_$i"; $addParam($field, $values[0]);
					break;
				case 'GT':
					$sql[] = "`$field` > :${field}_$i"; $addParam($field, $values[0]);
					break;
				case 'GTE':
					$sql[] = "`$field` >= :${field}_$i"; $addParam($field, $values[0]);
					break;
				case 'NULL':
					$sql[] = "`$field` IS NULL";
					break;
				case '!NULL':
					$sql[] = "`$field` IS NOT NULL";
					break;
				case 'BTW':
					$sql[] = "(`$field` >= :${field}_${i}_1 AND `$field` <= :${field}_${i}_2)"; $addParams($field, $values);
					break;
				case '!BTW':
					$sql[] = "(`$field` < :${field}_${i}_1 OR `$field` > :${field}_${i}_2)"; $addParams($field, $values);
					break;
				case 'IN':
					$in = '';
					for($j=1; $j<=count($values); $j++) $in .= sprintf(':%s_%d_%d, ',$field, $i, $j);
					$sql[] = sprintf('`%s` IN (%s)', $field, substr($in, 0, -2)); $addParams($field, $values);
					break;
				case '!IN':
					$in = '';
					for($j=1; $j<=count($values); $j++) $in .= sprintf(':%s_%d_%d, ',$field, $i, $j);
					$sql[] = sprintf('`%s` NOT IN (%s)', $field, substr($in, 0, -2)); $addParams($field, $values);
					break;
				case 'LIKE':
					$sql[] = "`$field` LIKE :${field}_$i"; $addParam($field, $values[0]);
					break;
				case '!LIKE':
					$sql[] = "`$field` NOT LIKE :${field}_$i"; $addParam($field, $values[0]);
					break;
				case 'LIKEHAS':
					$sql[] = "`$field` LIKE :${field}_$i"; $addParam($field, '%'.$values[0].'%');
					break;
				case '!LIKEHAS':
					$sql[] = "`$field` NOT LIKE :${field}_$i"; $addParam($field, '%'.$values[0].'%');
					break;
				case 'LIKESTART':
					$sql[] = "`$field` LIKE :${field}_$i"; $addParam($field, $values[0].'%');
					break;
				case '!LIKESTART':
					$sql[] = "`$field` NOT LIKE :${field}_$i"; $addParam($field, $values[0].'%');
					break;
				case 'LIKEEND':
					$sql[] = "`$field` LIKE :${field}_$i"; $addParam($field, '%'.$values[0]);
					break;
				case '!LIKEEND':
					$sql[] = "`$field` NOT LIKE :${field}_$i"; $addParam($field, '%'.$values[0]);
					break;
				default:
					trigger_error(__METHOD__.' - invalid criteriaExp: '.$cExp, E_USER_ERROR);
			}
		}
		$this->params = array_merge($this->params, $params);
		return (empty($sql)) ? '' : 'WHERE '.implode(' AND ',$sql);
	}
}
