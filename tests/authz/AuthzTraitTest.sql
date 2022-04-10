
INSERT INTO sys_users (id, name) VALUES
	(1, 'John Red'),
	(2, 'Jack Brown'),
	(3, 'Robert Green'),
	(4, 'Dick Yellow'),
	(5, 'Dick Blue')
;

INSERT INTO sys_authz (id, type, code, query) VALUES
	(1,		'ROLE',			'role.service',	NULL),
	(2,		'ROLE',			'role.service.foo',	NULL),
	(3,		'ROLE',			'role.service.bar',	NULL),
	(4,		'PERMISSION',	'perm.service',	NULL),
	(5,		'PERMISSION',	'perm.service.foo',	NULL),
	(6,		'PERMISSION',	'perm.service.bar',	NULL)
;

INSERT INTO sys_authz_maps (type, user_id, authz_id) VALUES
	('USER_ROLE', 1, 1),	-- role.service
	('USER_ROLE', 1, 2),	-- role.service.foo

	('USER_ROLE', 2, 1),	-- role.service
	('USER_ROLE', 2, 3),	-- role.service.bar

	('USER_ROLE', 3, 1),	-- role.service
	('USER_ROLE', 3, 2),	-- role.service.foo
	('USER_ROLE', 3, 3),	-- role.service.bar

	('USER_ROLE', 5, 1),	-- role.service

	('USER_PERMISSION', 1, 4),	-- perm.service
	('USER_PERMISSION', 1, 5),	-- perm.service.foo

	('USER_PERMISSION', 2, 4),	-- perm.service
	('USER_PERMISSION', 2, 6),	-- perm.service.bar

	('USER_PERMISSION', 3, 4),	-- perm.service
	('USER_PERMISSION', 3, 5),	-- perm.service.foo
	('USER_PERMISSION', 3, 6)	-- perm.service.bar


;
