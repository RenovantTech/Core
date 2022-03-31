
INSERT INTO sys_users (id, name) VALUES
	(1, 'John Admin'),
	(2, 'Jack Redactor'),
	(3, 'Don User'),
	(4, 'Brian Special User')
;

INSERT INTO sys_authz (id, type, code, query) VALUES
	(1, 'ROLE', 'ADMIN', null),
	(2, 'ROLE', 'STAFF', null),
	(3, 'ROLE', 'USER', null),
	(4, 'ACTION', 'api.users', null),
	(5, 'ACTION', 'api.users.insert', null),
	(6, 'ACTION', 'service.Foo', null),
	(7, 'ACTION', 'service.Bar', null),
	(8, 'ACTION', 'data.UserRepository', null),
	(9, 'ACTION', 'data.UserRepository.FETCH', null),
	(10, 'FILTER', 'data.UserRepository', 'SELECT 1'),
	(11, 'FILTER', 'data.UserRepository.FETCH', 'SELECT 1')
;

INSERT INTO sys_authz_maps (type, user_id, authz_id) VALUES
	('USER_ROLE', 1, 1),
	('USER_ROLE', 2, 2),
	('USER_ROLE', 3, 3),
	('USER_ROLE', 4, 3),
	('USER_ACTION', 1, 4),
	('USER_ACTION', 1, 6),
	('USER_ACTION', 2, 7),
	('USER_ACTION', 4, 7),
	('USER_FILTER', 1, 10),
	('USER_FILTER', 1, 11),
	('USER_FILTER', 2, 10),
	('USER_FILTER', 4, 10)
;

INSERT INTO sys_authz_rules (type, target, method, params_regex, authz_id) VALUES
	('URL',		'^/api/users/',			NULL,		'', 1),
	('URL',		'^/api/users/$',		'POST',		'', 2),
	('OBJECT',	'service.Foo',			'index',	'', 3),
	('OBJECT',	'service.Bar',			'index',	'', 4),
	('ORM',		'data.UserRepository',	NULL,		'', 5),
	('ORM',		'data.UserRepository',	'FETCH',	'', 6)
;
