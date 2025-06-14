
INSERT INTO sys_users (id, name) VALUES
	(1, 'John Red'),
	(2, 'Jack Brown'),
	(3, 'Robert Green'),
	(4, 'Dick Yellow'),
	(5, 'Dick Blue'),
	(6, 'Super Admin')
;

INSERT INTO sys_authz (id, type, code, config) VALUES
	(1,	'ROLE',			'sys-admin',		NULL),
	(2,	'ROLE',			'role.service',		NULL),
	(3,	'ROLE',			'role.service.foo',	NULL),
	(4,	'ROLE',			'role.service.bar',	NULL),
	(5,	'PERMISSION',	'perm.service',		NULL),
	(6,	'PERMISSION',	'perm.service.foo',	NULL),
	(7,	'PERMISSION',	'perm.service.bar',	NULL),
	(8,	'ACL',			'acl.foo',			NULL),
	(9,	'ACL',			'acl.area',			NULL),
	(10,'ACL',			'acl.district',		NULL)
;

INSERT INTO sys_authz_maps (type, user_id, authz_id, item_id) VALUES
	('USER_ROLE', 1, 2,	0),			-- role.service
	('USER_ROLE', 1, 3,	0),			-- role.service.foo

	('USER_ROLE', 2, 2,	0),			-- role.service
	('USER_ROLE', 2, 4,	0),			-- role.service.bar

	('USER_ROLE', 3, 2,	0),			-- role.service
	('USER_ROLE', 3, 3,	0),			-- role.service.foo
	('USER_ROLE', 3, 4,	0),			-- role.service.bar

	('USER_ROLE', 5, 2,	0),			-- role.service

	('USER_ROLE', 6, 1,	0),			-- role.service

	('USER_PERMISSION', 1, 5,	0),	-- perm.service
	('USER_PERMISSION', 1, 6,	0),	-- perm.service.foo

	('USER_PERMISSION', 2, 5,	0),	-- perm.service
	('USER_PERMISSION', 2, 7,	0),	-- perm.service.bar

	('USER_PERMISSION', 3, 5,	0),	-- perm.service
	('USER_PERMISSION', 3, 6,	0),	-- perm.service.foo
	('USER_PERMISSION', 3, 7,	0),	-- perm.service.bar

	('USER_PERMISSION', 5, 5,	0),	-- perm.service


	('USER_ACL', 1, 8,	123),	-- acl.foo
	('USER_ACL', 1, 8,	456),	-- acl.foo

	('USER_ACL', 2, 9,	1),		-- acl.area
	('USER_ACL', 2, 9,	2),		-- acl.area
	('USER_ACL', 2, 10,	1),		-- acl.district
	('USER_ACL', 2, 10,	2),		-- acl.district

	('USER_ACL', 3, 9,	1),		-- acl.area
	('USER_ACL', 3, 9,	2),		-- acl.area

	('USER_ACL', 4, 10,	1),		-- acl.district
	('USER_ACL', 4, 10,	2)		-- acl.district
;
