
INSERT INTO sys_users (id, name) VALUES
	(1, 'John Red'),
	(2, 'Jack Brown'),
	(3, 'Robert Green'),
	(4, 'Dick Yellow')
;

INSERT INTO sys_authz (id, type, code, query) VALUES
	(1,		'ROLE',		'role.service',	NULL),
	(2,		'ROLE',		'role.service.foo',	NULL),
	(3,		'ROLE',		'role.service.bar',	NULL)
;

INSERT INTO sys_authz_maps (type, user_id, authz_id) VALUES
	('USER_ROLE', 1, 1),	-- role.service
	('USER_ROLE', 1, 2),	-- role.service.foo

	('USER_ROLE', 2, 1),	-- role.service
	('USER_ROLE', 2, 3),	-- role.service.bar

	('USER_ROLE', 3, 1),	-- role.service
	('USER_ROLE', 3, 2),	-- role.service.foo
	('USER_ROLE', 3, 3)		-- role.service.bar
;
