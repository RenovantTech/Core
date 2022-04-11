/* USERS & GROUPS */

CREATE TABLE IF NOT EXISTS t_users (
	id		INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	name	VARCHAR(30) NOT NULL,
	surname	VARCHAR(20),
	email	VARCHAR(30) NULL DEFAULT NULL,
	PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* AUTHZ tables */

CREATE TABLE IF NOT EXISTS t_authz (
	id		INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	type	ENUM('ROLE', 'PERMISSION', 'ACL') NOT NULL,
	code	VARCHAR(40) NOT NULL,
	label	VARCHAR(100) NOT NULL DEFAULT '',
	query	VARCHAR(1024) NULL DEFAULT NULL,
	PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS t_authz_maps (
	type		ENUM('USER_ROLE', 'USER_PERMISSION', 'USER_ACL') NOT NULL,
	user_id		INTEGER UNSIGNED NULL DEFAULT NULL,
	authz_id	INTEGER UNSIGNED NULL DEFAULT NULL,
	data		VARCHAR(1024) NULL DEFAULT NULL,
	UNIQUE KEY uk_t_authz_maps (type, user_id, authz_id),
	CONSTRAINT fk_t_authz_maps__user_id FOREIGN KEY (user_id) REFERENCES t_users (id),
	CONSTRAINT fk_t_authz_maps__authz_id FOREIGN KEY (authz_id) REFERENCES t_authz (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS t_authz_rules (
	type		ENUM('URL', 'OBJECT', 'ORM') NOT NULL,
	target		VARCHAR(100) NOT NULL,
	method		VARCHAR(40) NULL DEFAULT NULL,
	params_regex VARCHAR(100) NOT NULL,
	authz_id	INTEGER UNSIGNED NOT NULL,
	UNIQUE KEY uk_t_authz_rules (type, target, method),
	CONSTRAINT fk_t_authz_rules__authz_id FOREIGN KEY (authz_id) REFERENCES t_authz (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP VIEW IF EXISTS vw_t_authz_maps;
CREATE VIEW vw_t_authz_maps AS
	SELECT
		maps.*,
		CONCAT(u.name, ' ', u.surname) AS userName,
		authz.code		AS authzCode,
		authz.label		AS authzLabel
	FROM t_authz_maps maps
		 LEFT JOIN t_authz authz ON maps.authz_id = authz.id
		 LEFT JOIN t_users u ON maps.user_id = u.id
;
