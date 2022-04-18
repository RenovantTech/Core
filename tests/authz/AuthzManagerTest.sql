
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
