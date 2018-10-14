<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace metadigit\core\queue;
use const metadigit\core\trace\T_INFO;
use metadigit\core\sys;

class Queue {
	use \metadigit\core\CoreTrait;

	const DEFAULT_QUEUE = 'default';
	const SQL_ACK		= 'UPDATE %s SET status = "OK", timeOK = NOW() WHERE id = :id AND status = "RUNNING"';
	const SQL_PUSH		= 'INSERT INTO %s (queue, priority, delay, ttr, job) VALUES (:queue, :priority, :delay, :ttr, :job)';
	const SQL_RESERVE	= 'SELECT * FROM %s WHERE status = "WAITING" AND DATE_ADD(timeIN, INTERVAL delay SECOND) <= NOW() %s ORDER BY priority ASC, id ASC LIMIT 1';
	const SQL_RELEASE	= 'UPDATE %s SET status = "WAITING", attempt = attempt + 1 WHERE id = :id AND status = "RUNNING"';
	const SQL_STATS		= 'SELECT status, priority, delay, ttr, attempt FROM %s WHERE id = :id';
	const SQL_UPDATE	= 'UPDATE %s SET %s WHERE id = :id';

	const STATUS_WAITING	= 'WAITING';
	const STATUS_RUNNING	= 'RUNNING';
	const STATUS_OK			= 'OK';
	const STATUS_ERROR		= 'ERROR';

	/** PDO instance ID
	 * @var string */
	protected $pdo = 'master';
	/** DB table
	 * @var string */
	protected $table = 'sys_queue';

	/**
	 * Queue constructor.
	 * @param string $pdo PDO instance ID, default to "master"
	 * @param string $table
	 */
	function __construct($pdo='master', $table='sys_queue') {
		$prevTraceFn = sys::traceFn('sys.Queue');
		if ($pdo) $this->pdo = $pdo;
		if ($table) $this->table = $table;
		try {
			sys::trace(LOG_DEBUG, T_INFO, 'initialize QUEUE storage');
			$PDO = sys::pdo($this->pdo);
			$driver = $PDO->getAttribute(\PDO::ATTR_DRIVER_NAME);
			$PDO->exec(str_replace('sys_queue', $this->table, file_get_contents(__DIR__ . '/sql/init-' . $driver . '.sql')
			));
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	/**
	 * Acknowledge a message, to be removed from the queue
	 * @param $id
	 * @return bool TRUE on success
	 */
	function ack($id) {
		return (bool) sys::pdo($this->pdo)->prepare(sprintf(self::SQL_ACK, $this->table))
			->execute(['id'=>$id])->rowCount();
	}

	/** Check whether the job is waiting for execution
	 * @param $id
	 * @return bool
	 */
	function isWaiting($id) {
		$stats = $this->stats($id);
		return ($stats['status'] == self::STATUS_WAITING);
	}

	/** Check whether a worker got the job from the queue and executes it.
	 * @param $id
	 * @return bool
	 */
	function isRunning($id) {
		$stats = $this->stats($id);
		return ($stats['status'] == self::STATUS_RUNNING);
	}

	/** Check whether a worker has executed the job
	 * @param $id
	 * @return bool
	 */
	function isDone($id) {
		$stats = $this->stats($id);
		return ($stats['status'] == self::STATUS_OK);
	}

	/**
	 * Push a message in the queue
	 * @param mixed $job
	 * @param integer $priority
	 * @param string $queue
	 * @param integer $delay seconds
	 * @param integer|null $ttr
	 * @return int JOB id
	 */
	function push($job, $priority=100, $queue=self::DEFAULT_QUEUE, $delay=0, $ttr=null) {
		$prevTraceFn = sys::traceFn($this->_.'->'.__FUNCTION__);
		try {
			$job = serialize($job);
			sys::trace(LOG_DEBUG, T_INFO, '[PUSH] PRI: '.$priority.' QUEUE: '.$queue, $job);
			sys::pdo($this->pdo)->prepare(sprintf(self::SQL_PUSH, $this->table))
				->execute(['queue'=>$queue, 'priority'=>$priority, 'delay'=>$delay, 'ttr'=>$ttr, 'job'=>$job]);
			return (int)sys::pdo($this->pdo)->lastInsertId();
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	/**
	 * Release a message, to be picked up again by a new worker
	 * @param $id
	 * @return bool TRUE on success
	 */
	function release($id) {
		return (bool) sys::pdo($this->pdo)->prepare(sprintf(self::SQL_RELEASE, $this->table))
			->execute(['id'=>$id])->rowCount();
	}

	/**
	 * Takes one message from the queue and reserves it for handling
	 * @param null $queue
	 * @return array job ID & job data
	 */
	function reserve($queue=null) {
		$params = $queue ? ['queue'=>$queue] : null;
		$data = sys::pdo($this->pdo)->prepare(sprintf(self::SQL_RESERVE, $this->table, $queue?' AND queue = :queue ':null))
			->execute($params)->fetch(\PDO::FETCH_ASSOC);
		if($data) {
			$params = ['id'=>$data['id'], 'status'=>'RUNNING'];
			sys::pdo($this->pdo)->prepare(sprintf(self::SQL_UPDATE, $this->table, ' status = :status, timeRUN = CURRENT_TIMESTAMP '))
				->execute($params);
		}
		return [$data['id'], unserialize($data['job'])];
	}

	function stats($id) {
		return sys::pdo($this->pdo)->prepare(sprintf(self::SQL_STATS, $this->table))
			->execute(['id'=>$id])->fetch(\PDO::FETCH_ASSOC);
	}

}
