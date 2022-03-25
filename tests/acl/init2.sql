
INSERT INTO sys_users (id, name) VALUES
	(1, 'John Red'),
	(2, 'Jack Brown'),
	(3, 'Robert Green')
;

INSERT INTO sys_acl (id, type, code, query) VALUES
	(1,		'ROLE',		'role.service2',	NULL),
	(2,		'ACTION',	'action.foo',		NULL),
	(3,		'FILTER',	'filter.bar',		'SELECT 1')
;


INSERT INTO sys_acl_maps (type, user_id, acl_id) VALUES
	('USER_ROLE', 1, 1),
	('USER_ROLE', 2, 1),
	('USER_ACTION', 1, 2),
	('USER_FILTER', 2, 3)
;
