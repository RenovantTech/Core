
CREATE TABLE IF NOT EXISTS sys_queue (
	id				INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	status			ENUM('WAITING','RUNNING','OK','ERROR') NOT NULL DEFAULT 'WAITING',
	queue	 		VARCHAR(50) NOT NULL,
	priority		TINYINT UNSIGNED NOT NULL DEFAULT 100,
	delay			SMALLINT UNSIGNED NOT NULL DEFAULT 0,
	ttr				TINYINT UNSIGNED NULL DEFAULT NULL,
	attempt			TINYINT UNSIGNED NOT NULL DEFAULT 0,
	job				TEXT NOT NULL,

	timeIN			DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	timeRUN			DATETIME NULL DEFAULT NULL,
	timeOK			DATETIME NULL DEFAULT NULL,
	PRIMARY KEY (id),
	KEY sys_queue__queue (queue),
	KEY sys_queue__priority (priority),
	KEY sys_queue__timeIN (timeIN),
	KEY sys_queue__timeRUN (timeRUN)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;