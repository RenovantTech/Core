/* USERS */
INSERT INTO sys_users (id, name) VALUES
	(1, 'John Admin'),
	(2, 'Jack Redactor'),
	(3, 'Don User'),
	(4, 'Brian Special User')
;
/* ROLES */
INSERT INTO sys_acl (id, type, code) VALUES
	(1, 'ROLE', 'ADMIN'),
	(2, 'ROLE', 'STAFF'),
	(3, 'ROLE', 'USER')
;
/* USERS <=> ROLES */
INSERT INTO sys_acl_maps (type, user_id, acl_id) VALUES
	('USER_ROLE', 1, 1),
	('USER_ROLE', 2, 2),
	('USER_ROLE', 3, 3),
	('USER_ROLE', 4, 3)
;
/* ACTION */
INSERT INTO sys_acl (id, type, code) VALUES
	(4, 'ACTION', 'api.users'),
	(5, 'ACTION', 'api.users.insert'),
	(6, 'ACTION', 'service.Foo'),
	(7, 'ACTION', 'service.Bar'),
	(8, 'ACTION', 'data.UserRepository'),
	(9, 'ACTION', 'data.UserRepository.FETCH')
;
/* FILTERS */
INSERT INTO sys_acl (id, type, code, query) VALUES
	(10, 'FILTER', 'data.UserRepository', 'SELECT 1'),
	(11, 'FILTER', 'data.UserRepository.FETCH', 'SELECT 1')
;
/* ACL */
INSERT INTO sys_acl_rules (type, target, method, params_regex, acl_id) VALUES
	('URL',		'^/api/users/',			NULL,		'', 1),
	('URL',		'^/api/users/$',		'POST',		'', 2),
	('OBJECT',	'service.Foo',			'index',	'', 3),
	('OBJECT',	'service.Bar',			'index',	'', 4),
	('ORM',		'data.UserRepository',	NULL,		'', 5),
	('ORM',		'data.UserRepository',	'FETCH',	'', 6)
;
/* USERS <=> ACTION */
INSERT INTO sys_acl_maps (type, user_id, acl_id) VALUES
	('USER_ACTION', 1, 4),
	('USER_ACTION', 1, 6),
	('USER_ACTION', 2, 7),
	('USER_ACTION', 4, 7)
;

/* USERS <=> FILTER */
INSERT INTO sys_acl_maps (type, user_id, acl_id) VALUES
	('USER_FILTER', 1, 10),
	('USER_FILTER', 1, 11),
	('USER_FILTER', 2, 10),
	('USER_FILTER', 4, 10)
;
