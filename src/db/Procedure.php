<?php
namespace renovant\core\db;
use renovant\core\sys;
class Procedure {
	use \renovant\core\CoreTrait;

	/** PDO instance ID
	 * @var string */
	protected string $pdo;
	/** PDOStatement
	 * @var \PDOStatement */
	protected $PDOStatement;
	/** SQL procedure */
	protected string $procedure;

	function __construct(string $procedure, ?string $pdo=null) {
		$this->procedure = $procedure;
		$this->pdo = $pdo;
	}

	/**
	 * Execute CALL statement
	 * @param array $params
	 * @return array|true|null output params if any
	 */
	function exec(array $params=[]): array|bool|null {
		if(is_null($this->PDOStatement)) {
			$sql = '';
			$outputParams = [];
			if(!empty($params)) {
				foreach($params as $k=>$v){
					if(is_string($v) && $v[0]=='@') {
						$sql .= ', '.$v;
						$outputParams[] = $v;
						unset($params[$k]);
					} else {
						$sql .= ', :'.$k;
					}
				}
				$sql = substr($sql,2);
			}
			$sql = sprintf('CALL %s(%s)', $this->procedure, $sql);
			$this->PDOStatement = sys::pdo($this->pdo)->prepare($sql);
		}
		$this->PDOStatement->execute($params);

		if(empty($outputParams))
			return true;
		else {
			$keys = [];
			$sql = sprintf('SELECT %s', implode(', ', $outputParams));
			foreach($outputParams as $p) $keys[] = substr($p,1);
			return array_combine($keys, sys::pdo($this->pdo)->query($sql)->fetch(\PDO::FETCH_NUM));
		}
	}

	function errorCode() {
		return $this->PDOStatement->errorCode();
	}
}
