/* USERS */
INSERT INTO sys_users (id, name) VALUES
	(1, 'John Admin'),
	(2, 'Jack Redactor'),
	(3, 'Don User'),
	(4, 'Brian Special User')
;
/* GROUPS */
INSERT INTO sys_groups (id, name) VALUES
	(1, 'ADMIN'),
	(2, 'STAFF'),
	(3, 'USER')
;
/* USERS 2 GROUPS */
INSERT INTO sys_users_2_groups (user_id, group_id) VALUES
	(1, 1),
	(2, 2),
	(3, 3),
	(4, 3)
;
/* ACTION */
INSERT INTO sys_acl_actions (id, name) VALUES
	(1, 'api.users'),
	(2, 'api.users.insert'),
	(3, 'service.Foo'),
	(4, 'service.Bar'),
	(5, 'data.UserRepository.INSERT'),
	(6, 'data.UserRepository.DELETE')
;
/* FILTERS */
INSERT INTO sys_acl_filters (id, name) VALUES
	(1, 'filter1')
;
/* FILTERS SQL */
INSERT INTO sys_acl_filters_sql (id, query) VALUES
	(1, 'SELECT 1')
;
/* ACL */
INSERT INTO sys_acl (type, target, method, params_regex, action, filter, filter_sql) VALUES
	('URL',		'^/api/users/$',		'GET',		'', 1, NULL, NULL),
	('URL',		'^/api/users/$',		'POST',		'', 2, NULL, NULL),
	('OBJECT',	'service.Foo',			'index',	'', 3, 1, 1),
	('OBJECT',	'service.Bar',			'index',	'', 4, 1, 1),
	('ORM',		'data.UserRepository',	'INSERT',	'', 5, 1, 1),
	('ORM',		'data.UserRepository',	'DELETE',	'', 6, 1, 1)
;
/* ACTION 2 USERS */
INSERT INTO sys_acl_actions_2_users (action_id, user_id) VALUES
	(2, 4)
;
INSERT INTO sys_acl_actions_2_groups (action_id, group_id) VALUES
	(1, 1),
	(2, 1),
	(3, 1),
	(4, 1),
	(5, 1),
	(6, 1),
	(1, 2)
;
