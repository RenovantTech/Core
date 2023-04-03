
INSERT INTO sys_users (id, name) VALUES
	(1, 'John Red'),
	(2, 'Jack Brown'),
	(3, 'Robert Green'),
	(4, 'Dick Yellow'),
	(5, 'Dick Blue')
;

INSERT INTO sys_authz (id, type, code, config) VALUES
	(1,	'ROLE',			'role.service',		NULL),
	(2,	'ROLE',			'role.service.foo',	NULL),
	(3,	'ROLE',			'role.service.bar',	NULL),
	(4,	'PERMISSION',	'perm.service',		NULL),
	(5,	'PERMISSION',	'perm.service.foo',	NULL),
	(6,	'PERMISSION',	'perm.service.bar',	NULL),
	(7,	'ACL',			'acl.foo',			NULL),
	(8,	'ACL',			'acl.area',			NULL),
	(9,	'ACL',			'acl.district',		NULL)
;

INSERT INTO sys_authz_maps (type, user_id, authz_id, item_id) VALUES
	('USER_ROLE', 1, 1,	NULL),	-- role.service
	('USER_ROLE', 1, 2,	NULL),	-- role.service.foo

	('USER_ROLE', 2, 1,	NULL),	-- role.service
	('USER_ROLE', 2, 3,	NULL),	-- role.service.bar

	('USER_ROLE', 3, 1,	NULL),	-- role.service
	('USER_ROLE', 3, 2,	NULL),	-- role.service.foo
	('USER_ROLE', 3, 3,	NULL),	-- role.service.bar

	('USER_ROLE', 5, 1,	NULL),	-- role.service

	('USER_PERMISSION', 1, 4,	NULL),	-- perm.service
	('USER_PERMISSION', 1, 5,	NULL),	-- perm.service.foo

	('USER_PERMISSION', 2, 4,	NULL),	-- perm.service
	('USER_PERMISSION', 2, 6,	NULL),	-- perm.service.bar

	('USER_PERMISSION', 3, 4,	NULL),	-- perm.service
	('USER_PERMISSION', 3, 5,	NULL),	-- perm.service.foo
	('USER_PERMISSION', 3, 6,	NULL),	-- perm.service.bar

	('USER_PERMISSION', 5, 4,	NULL),	-- role.service


	('USER_ACL', 1, 7,	123),	-- acl.foo
	('USER_ACL', 1, 7,	456),	-- acl.foo

	('USER_ACL', 2, 8,	1),		-- acl.area
	('USER_ACL', 2, 8,	2),		-- acl.area
	('USER_ACL', 2, 9,	1),		-- acl.district
	('USER_ACL', 2, 9,	2),		-- acl.district

	('USER_ACL', 3, 8,	1),		-- acl.area
	('USER_ACL', 3, 8,	2),		-- acl.area

	('USER_ACL', 4, 9,	1),		-- acl.district
	('USER_ACL', 4, 9,	2)		-- acl.district
;
