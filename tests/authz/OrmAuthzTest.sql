
INSERT INTO sys_users (id, name) VALUES
	(1, 'John Red'),
	(2, 'Jack Brown'),
	(3, 'Robert Green'),
	(4, 'Dick Yellow'),
	(5, 'Dick Blue')
;

INSERT INTO sys_authz (id, type, code, config) VALUES
	(1,	'ROLE',			'admin',			NULL),
	(2,	'ROLE',			'manager',			NULL),
	(3,	'PERMISSION',	'users:manage',		NULL),
	(4,	'PERMISSION',	'users:insert',		NULL),
	(5,	'PERMISSION',	'users:update',		NULL),
	(6,	'ACL',			'schools',			NULL)
;

INSERT INTO sys_authz_maps (type, user_id, authz_id, data) VALUES
	('USER_ROLE',		1, 1,	NULL),		-- admin
	('USER_PERMISSION', 1, 3,	NULL),		-- users:manage
	('USER_PERMISSION', 1, 5,	NULL),		-- users:update
	('USER_ACL',		1, 6,	'[1,3]'),	-- schools

	('USER_ROLE', 		2, 2,	NULL),	-- manager


	('USER_ROLE',		3, 1,	NULL),		-- admin
	('USER_PERMISSION', 3, 3,	NULL)		-- users:manage
;

CREATE TABLE IF NOT EXISTS classes (
	id					INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
	school_id			MEDIUMINT UNSIGNED NOT NULL,
	center_id			MEDIUMINT UNSIGNED NULL DEFAULT NULL,
	type_id				SMALLINT UNSIGNED NOT NULL,

	status				ENUM('ACTIVE','NEW','OLD') NOT NULL,
	code				VARCHAR(25) NULL DEFAULT NULL,
	name				VARCHAR(50) NOT NULL,
	level				VARCHAR(50) NULL DEFAULT NULL,
	dateStart			DATE NOT NULL,
	dateEnd				DATE NOT NULL,
	PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO classes (id, school_id, center_id, type_id, status, code, name, level, dateStart, dateEnd)
VALUES (1, 1, 1, 0, 'ACTIVE', 'PHIS1', 'Phisics', 'Basic', '2022-01-01', '2022-06-30'),
       (2, 1, 2, 0, 'ACTIVE', 'MATH1', 'Mathematics', 'Basic', '2022-01-01', '2022-06-30'),
       (3, 2, 1, 0, 'ACTIVE', 'SC1', 'Sciences', 'Basic', '2022-01-01', '2022-06-30'),
       (4, 2, 2, 0, 'ACTIVE', 'CHE1', 'Chemicals', 'Basic', '2022-01-01', '2022-06-30'),
       (5, 3, 1, 0, 'ACTIVE', 'SC1', 'Sciences', 'Basic', '2022-01-01', '2022-06-30'),
       (6, 3, 2, 0, 'ACTIVE', 'CHE1', 'Chemicals', 'Basic', '2022-01-01', '2022-06-30')
;
