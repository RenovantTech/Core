<?php
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
	const SQL_RELEASE	= 'UPDATE %s SET status = :status, attempt = attempt + 1 WHERE id = :id AND status = "RUNNING"';
	const SQL_STATS		= 'SELECT status, priority, delay, ttr, attempt FROM %s WHERE id = :id';
	const SQL_UPDATE	= 'UPDATE %s SET %s WHERE id = :id';
	const SQL_CHECK_RUNNING	= 'SELECT id, TIME_TO_SEC(TIMEDIFF(NOW(), timeRUN)) AS secs FROM %s WHERE status = "RUNNING" AND timeIN >= :timeIN ORDER BY timeRUN ASC';
	const SQL_CHECK_STATUS = 'SELECT status, COUNT(1) AS n FROM %s WHERE timeIN >= :timeIN GROUP BY status';

	const STATUS_WAITING	= 'WAITING';
	const STATUS_RUNNING	= 'RUNNING';
	const STATUS_OK			= 'OK';
	const STATUS_ERROR		= 'ERROR';

	/** PDO instance ID
	 * @var string */
	protected string $pdo;
	/** DB table
	 * @var string */
	protected string $table = 'sys_queue';

	/**
	 * Queue constructor.
	 * @param string|null $pdo PDO instance ID, default to "master"
	 * @param string $table
	 */
	function __construct(?string $pdo=null, string $table='sys_queue') {
		$prevTraceFn = sys::traceFn('sys.Queue');
		$this->pdo = $pdo;
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
	 * Release a message, to be picked up again by a new worker, or to be left as error
	 * @param int $id
	 * @param bool $retry
	 * @return bool TRUE on success
	 */
	function release(int $id, bool $retry=true): bool {
		$status = $retry ? self::STATUS_WAITING : self::STATUS_ERROR;
		return (bool) sys::pdo($this->pdo)->prepare(sprintf(self::SQL_RELEASE, $this->table))
			->execute(['id'=>$id, 'status'=>$status], false)->rowCount();
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

	/**
	 * Check Queue status in the last $seconds (whole Queue if $seconds is NULL)
	 * @param int|null $seconds
	 * @return array
	 */
	function status(?int $seconds=null): array {
		$data = [
			'WAITING' => 0,
			'RUNNING' => 0,
			'OK' => 0,
			'ERROR' => 0,
			'runMax' => 0,
			'runMin' => 0,
			'run10s' => 0,
			'run1m' => 0,
			'run10m' => 0,
			'run1h' => 0
		];
		$timeIN = ($seconds) ? date('Y-m-d H:i:s', time()-$seconds) : '0000-00-00 00:00:00';

		$status = sys::pdo($this->pdo)->prepare(sprintf(self::SQL_CHECK_STATUS, $this->table))
			->execute(['timeIN'=> $timeIN])->fetchAll(\PDO::FETCH_ASSOC);
		foreach($status as $s)
			$data[$s['status']] = $s['n'];

		$running = sys::pdo($this->pdo)->prepare(sprintf(self::SQL_CHECK_RUNNING, $this->table))
			->execute(['timeIN' => $timeIN])->fetchAll(\PDO::FETCH_ASSOC);
		if(count($running)) {
			$data['runMax'] = $running[0]['secs'];
			$data['runMin'] = end($running)['secs'];
		}
		foreach ($running as $r) {
			if($r['secs'] >= 10) $data['run10s']++;
			if($r['secs'] >= 60) $data['run1m']++;
			if($r['secs'] >= 600) $data['run10m']++;
			if($r['secs'] >= 3600) $data['run1h']++;
		}

		return $data;
	}
}
