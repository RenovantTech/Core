
INSERT INTO sys_users (id, name) VALUES
	(1, 'John Admin'),
	(2, 'Jack Redactor'),
	(3, 'Don User'),
	(4, 'Brian Special User')
;

INSERT INTO sys_authz (id, type, code, config) VALUES
	(1, 'ROLE', 		'ADMIN',		NULL),
	(2, 'ROLE', 		'STAFF',		NULL),
	(3, 'ROLE', 		'USER',			NULL),
	(4,	'PERMISSION',	'blog.edit',	NULL),
	(5,	'PERMISSION',	'blog.delete',	NULL),
	(6,	'PERMISSION',	'blog.master',	NULL),
	(7,	'ACL',			'blog.author',	NULL)
;

INSERT INTO sys_authz_maps (type, user_id, authz_id, item_id) VALUES
	('USER_ROLE', 1, 1,	NULL),	-- ADMIN
	('USER_ROLE', 2, 2,	NULL),	-- STAFF
	('USER_ROLE', 3, 3,	NULL),	-- USER
	('USER_ROLE', 4, 3,	NULL),	-- USER

	('USER_PERMISSION', 1, 4,	NULL),	-- blog.edit
	('USER_PERMISSION', 1, 5,	NULL),	-- blog.delete

	('USER_ACL', 1, 7, 123),	-- blog.author
	('USER_ACL', 1, 7, 456)		-- blog.author
;
