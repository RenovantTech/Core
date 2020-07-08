<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\queue;
use const renovant\core\trace\T_INFO;
use renovant\core\sys;

class Queue {
	use \renovant\core\CoreTrait;

	const DEFAULT_QUEUE = 'default';
	const SQL_ACK		= 'UPDATE %s SET status = "OK", timeOK = NOW() WHERE id = :id AND status = "RUNNING"';
	const SQL_DATA		= 'SELECT data FROM %s WHERE id = :id';
	const SQL_PUSH		= 'INSERT INTO %s (queue, priority, delay, ttr, data) VALUES (:queue, :priority, :delay, :ttr, :data)';
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
	protected string $pdo = 'master';
	/** DB table
	 * @var string */
	protected string $table = 'sys_queue';

	/**
	 * Queue constructor.
	 * @param string $pdo PDO instance ID, default to "master"
	 * @param string $table
	 */
	function __construct(string $pdo='master', string $table='sys_queue') {
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
	 * @param int $id
	 * @return bool TRUE on success
	 */
	function ack(int $id): bool {
		return (bool) sys::pdo($this->pdo)->prepare(sprintf(self::SQL_ACK, $this->table))
			->execute(['id'=>$id], false)->rowCount();
	}

	/**
	 * Fetch job data
	 * @param int $id job ID
	 * @return mixed
	 */
	function data(int $id) {
		$data = unserialize(sys::pdo($this->pdo)->prepare(sprintf(self::SQL_DATA, $this->table))
			->execute(['id'=>$id], false)->fetch(\PDO::FETCH_COLUMN));
		sys::trace(LOG_DEBUG, T_INFO, '[DATA] JOB: '.$id, $data);
		return $data;
	}

	/** Check whether the job is waiting for execution
	 * @param int $id
	 * @return bool
	 */
	function isWaiting(int $id): bool {
		$stats = $this->stats($id);
		return ($stats['status'] == self::STATUS_WAITING);
	}

	/** Check whether a worker got the job from the queue and executes it.
	 * @param int $id
	 * @return bool
	 */
	function isRunning(int $id): bool {
		$stats = $this->stats($id);
		return ($stats['status'] == self::STATUS_RUNNING);
	}

	/** Check whether a worker has executed the job
	 * @param int $id
	 * @return bool
	 */
	function isDone(int $id): bool {
		$stats = $this->stats($id);
		return ($stats['status'] == self::STATUS_OK);
	}

	/**
	 * Push a message in the queue
	 * @param mixed $data data
	 * @param integer $priority
	 * @param string $queue
	 * @param integer $delay seconds
	 * @param integer|null $ttr
	 * @return int JOB id
	 */
	function push($data, int $priority=100, string $queue=self::DEFAULT_QUEUE, int $delay=0, ?int $ttr=null): int {
		$prevTraceFn = sys::traceFn($this->_.'->'.__FUNCTION__);
		try {
			sys::trace(LOG_DEBUG, T_INFO, '[PUSH] PRI: '.$priority.' QUEUE: '.$queue, $data);
			sys::pdo($this->pdo)->prepare(sprintf(self::SQL_PUSH, $this->table))
				->execute(['queue'=>$queue, 'priority'=>$priority, 'delay'=>$delay, 'ttr'=>$ttr, 'data'=>serialize($data)], false);
			return (int)sys::pdo($this->pdo)->lastInsertId();
		} finally {
			sys::traceFn($prevTraceFn);
		}
	}

	/**
	 * Release a message, to be picked up again by a new worker
	 * @param int $id
	 * @return bool TRUE on success
	 */
	function release(int $id): bool {
		return (bool) sys::pdo($this->pdo)->prepare(sprintf(self::SQL_RELEASE, $this->table))
			->execute(['id'=>$id], false)->rowCount();
	}

	/**
	 * Takes one message from the queue and reserves it for handling
	 * @param string|null $queue
	 * @return array job ID & job data
	 */
	function reserve(?string $queue=null): array {
		$params = $queue ? ['queue'=>$queue] : null;
		$data = sys::pdo($this->pdo)->prepare(sprintf(self::SQL_RESERVE, $this->table, $queue?' AND queue = :queue ':null))
			->execute($params, false)->fetch(\PDO::FETCH_ASSOC);
		if($data) {
			$params = ['id'=>$data['id'], 'status'=>'RUNNING'];
			sys::pdo($this->pdo)->prepare(sprintf(self::SQL_UPDATE, $this->table, ' status = :status, timeRUN = CURRENT_TIMESTAMP '))
				->execute($params, false);
			return [$data['id'], unserialize($data['data'])];
		} else return [null, null];
	}

	function stats(int $id): array {
		return sys::pdo($this->pdo)->prepare(sprintf(self::SQL_STATS, $this->table))
			->execute(['id'=>$id], false)->fetch(\PDO::FETCH_ASSOC);
	}
}
