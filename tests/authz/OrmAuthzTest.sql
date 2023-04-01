
INSERT INTO sys_users (id, name) VALUES
	(1, 'John Red'),
	(2, 'Jack Brown'),
	(3, 'Robert Green'),
	(4, 'Dick Yellow'),
	(5, 'Dick Blue')
;

INSERT INTO sys_authz (id, type, code, config) VALUES
	(1,	'ROLE',			'sys-admin',		NULL),
	(2,	'ROLE',			'admin:insert',		NULL),
	(3,	'ROLE',			'admin:select1',	NULL),
	(4,	'ROLE',			'admin:select2',	NULL),
	(5,	'ROLE',			'admin:update1',	NULL),
	(6,	'ROLE',			'admin:update2',	NULL),
	(7,	'PERMISSION',	'perm:all',			NULL),
	(8,	'PERMISSION',	'perm:insert',		NULL),
	(9,	'PERMISSION',	'perm:select1',		NULL),
	(10,'PERMISSION',	'perm:select2',		NULL),
	(11,'PERMISSION',	'perm:update1',		NULL),
	(12,'PERMISSION',	'perm:update2',		NULL),
	(13,'ACL',			'acl:id',			NULL),
	(14,'ACL',			'acl:school',		NULL),
	(15,'ACL',			'acl:type',			NULL)
;

INSERT INTO sys_authz_maps (type, user_id, authz_id, item_id) VALUES
	('USER_ROLE',		1, 1,	NULL),				-- sys-admin
	('USER_PERMISSION',	1, 7,	NULL),				-- perm:all
	('USER_ACL',		1, 13,	1),					-- acl:id
	('USER_ACL',		1, 13,	2),					-- acl:id
	('USER_ACL',		1, 13,	3),					-- acl:id
	('USER_ACL',		1, 13,	4),					-- acl:id
	('USER_ACL',		1, 13,	5),					-- acl:id
	('USER_ACL',		1, 13,	6),					-- acl:id
	('USER_ACL',		1, 14,	1),					-- acl:school
	('USER_ACL',		1, 14,	2),					-- acl:school
	('USER_ACL',		1, 14,	3),					-- acl:school
	('USER_ACL',		1, 15,	0),					-- acl:type
	('USER_ACL',		1, 15,	1),					-- acl:type
	('USER_ACL',		1, 15,	2),					-- acl:type
	('USER_ACL',		1, 15,	3),					-- acl:type

	('USER_ROLE', 		2, 2,	NULL),				-- admin:insert
	('USER_ROLE', 		2, 3,	NULL),				-- admin:select1
	('USER_PERMISSION',	2, 8,	NULL),				-- perm:insert
	('USER_PERMISSION',	2, 9,	NULL),				-- perm:select1
	('USER_ACL',		2, 13,	1),					-- acl:id
	('USER_ACL',		2, 13,	2),					-- acl:id
	('USER_ACL',		2, 13,	3),					-- acl:id
	('USER_ACL',		2, 13,	4),					-- acl:id
	('USER_ACL',		2, 13,	5),					-- acl:id
	('USER_ACL',		2, 13,	6),					-- acl:id

	('USER_ROLE', 		3, 5,	NULL),				-- admin:update1
	('USER_ROLE', 		3, 6,	NULL),				-- admin:update2
	('USER_PERMISSION',	3, 11,	NULL),				-- perm:update1
	('USER_PERMISSION',	3, 12,	NULL),				-- perm:update2
	('USER_ACL',		3, 13,	1),					-- acl:id
	('USER_ACL',		3, 13,	2),					-- acl:id
	('USER_ACL',		3, 13,	3),					-- acl:id
	('USER_ACL',		3, 13,	4),					-- acl:id
	('USER_ACL',		3, 13,	5),					-- acl:id
	('USER_ACL',		3, 13,	6)					-- acl:id
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
	PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO classes (id, school_id, center_id, type_id, status, code, name, level)
VALUES (1, 1, 1, 0, 'ACTIVE', 'PHIS1', 'Phisics', 'Basic'),
       (2, 1, 2, 0, 'ACTIVE', 'MATH1', 'Mathematics', 'Basic'),
       (3, 2, 1, 0, 'ACTIVE', 'SC1', 'Sciences', 'Basic'),
       (4, 2, 2, 0, 'ACTIVE', 'CHE1', 'Chemicals', 'Basic'),
       (5, 3, 1, 0, 'ACTIVE', 'SC1', 'Sciences', 'Basic'),
       (6, 3, 2, 0, 'ACTIVE', 'CHE1', 'Chemicals', 'Basic')
;
