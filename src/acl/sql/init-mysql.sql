/* USERS & GROUPS */

CREATE TABLE IF NOT EXISTS t_users (
	id		INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	name	VARCHAR(30) NOT NULL,
	PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS t_groups (
	id		INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	name	VARCHAR(30) NOT NULL,
	PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS t_u2g (
	user_id		INTEGER UNSIGNED NOT NULL,
	group_id	INTEGER UNSIGNED NOT NULL,
	PRIMARY KEY (user_id, group_id),
	CONSTRAINT fk_t_u2g_user FOREIGN KEY (user_id) REFERENCES t_users (id),
	CONSTRAINT fk_t_u2g_group FOREIGN KEY (group_id) REFERENCES t_groups (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* ACL tables */

CREATE TABLE IF NOT EXISTS acl_actions (
	id		INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	name	VARCHAR(30) NOT NULL,
	description	VARCHAR(100),
	PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS acl_filters (
	id		INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	name	VARCHAR(30) NOT NULL,
	PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS acl_filters_sql (
	id		INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	query	VARCHAR(255) NOT NULL,
	PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS acl (
	type		ENUM('URL', 'OBJECT', 'ORM') NOT NULL,
	target		VARCHAR(100) NOT NULL,
	method		VARCHAR(40) NOT NULL,
	params_regex VARCHAR(100) NOT NULL,
	action		INTEGER UNSIGNED NULL,
	filter		INTEGER UNSIGNED NULL,
	filter_sql	INTEGER UNSIGNED NULL,
	PRIMARY KEY (type, target, method),
	CONSTRAINT fk_acl_actions FOREIGN KEY (action) REFERENCES acl_actions (id),
	CONSTRAINT fk_acl_filters FOREIGN KEY (filter) REFERENCES acl_filters (id),
	CONSTRAINT fk_acl_filter_sql FOREIGN KEY (filter_sql) REFERENCES acl_filters_sql (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* ACL mappings */

CREATE TABLE IF NOT EXISTS acl_actions_2_users (
	action_id	INTEGER UNSIGNED NOT NULL,
	user_id		INTEGER UNSIGNED NOT NULL,
	PRIMARY KEY (action_id, user_id),
	CONSTRAINT fk_acl_actions_2_users_action FOREIGN KEY (action_id) REFERENCES acl_actions (id) ON DELETE CASCADE,
	CONSTRAINT fk_acl_actions_2_users_user FOREIGN KEY (user_id) REFERENCES t_users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS acl_actions_2_groups (
	action_id	INTEGER UNSIGNED NOT NULL,
	group_id	INTEGER UNSIGNED NOT NULL,
	PRIMARY KEY (action_id, group_id),
	CONSTRAINT fk_acl_actions_2_groups_action FOREIGN KEY (action_id) REFERENCES acl_actions (id) ON DELETE CASCADE,
	CONSTRAINT fk_acl_actions_2_groups_group FOREIGN KEY (group_id) REFERENCES t_groups (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS acl_filters_2_users (
	filter_id	INTEGER UNSIGNED NOT NULL,
	user_id		INTEGER UNSIGNED NOT NULL,
	val			VARCHAR(50) NULL,
	PRIMARY KEY (filter_id, user_id, val),
	CONSTRAINT fk_acl_filters_2_users_filter FOREIGN KEY (filter_id) REFERENCES acl_filters (id) ON DELETE CASCADE,
	CONSTRAINT fk_acl_filters_2_users_user FOREIGN KEY (user_id) REFERENCES t_users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS acl_filters_2_groups (
	filter_id	INTEGER UNSIGNED NOT NULL,
	group_id	INTEGER UNSIGNED NOT NULL,
	val			VARCHAR(50) NULL,
	PRIMARY KEY (filter_id, group_id, val),
	CONSTRAINT fk_acl_filters_2_groups_filter FOREIGN KEY (filter_id) REFERENCES acl_filters (id) ON DELETE CASCADE,
	CONSTRAINT fk_acl_filters_2_groups_group FOREIGN KEY (group_id) REFERENCES t_groups (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
