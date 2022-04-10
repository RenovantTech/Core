
INSERT INTO sys_users (id, name) VALUES
	(1, 'John Admin'),
	(2, 'Jack Redactor'),
	(3, 'Don User'),
	(4, 'Brian Special User')
;

INSERT INTO sys_authz (id, type, code, query) VALUES
	(1, 'ROLE', 'ADMIN', null),
	(2, 'ROLE', 'STAFF', null),
	(3, 'ROLE', 'USER', null)
;

INSERT INTO sys_authz_maps (type, user_id, authz_id) VALUES
	('USER_ROLE', 1, 1),
	('USER_ROLE', 2, 2),
	('USER_ROLE', 3, 3),
	('USER_ROLE', 4, 3)
;

INSERT INTO sys_authz_rules (type, target, method, params_regex, authz_id) VALUES
	('URL',		'^/api/users/',			NULL,		'', 1),
	('URL',		'^/api/users/$',		'POST',		'', 2),
	('OBJECT',	'service.Foo',			'index',	'', 3)
;
