<?php
namespace renovant\core\authz;
use renovant\core\util\reflection\DocComment,
	renovant\core\util\reflection\ReflectionClass;
/**
 * @internal
 */
class OrmTagsParser {

	static function parse(string $entityClass): ?OrmAuthz {
		try {
			$RefClass = new ReflectionClass($entityClass);
			$DocComment = $RefClass->getDocComment();
			$allows = $roles = $perms = $acls = $op_roles = $op_perms = $op_acls = null;
			$actions = [
				OrmAuthz::ACTION_ALL,
				OrmAuthz::ACTION_INSERT,
				OrmAuthz::ACTION_SELECT,
				OrmAuthz::ACTION_UPDATE,
				OrmAuthz::ACTION_DELETE
			];
			foreach ($actions as $action) {
				$a = self::parseAllows($DocComment, $action);
				if(!empty($a)) {
					$allows[$action] = $a;
				}
				list($r, $op) = self::parseRoles($DocComment, $action);
				if(!empty($r)) {
					$roles[$action] = $r;
					$op_roles[$action] = $op;
				}
				list($p, $op) = self::parsePermissions($DocComment, $action);
				if(!empty($p)) {
					$perms[$action] = $p;
					$op_perms[$action] = $op;
				}
				list($a, $op) = self::parseAcls($DocComment, $action);
				if(!empty($a)) {
					$acls[$action] = $a;
					$op_acls[$action] = $op;
				}
			}
			if(empty($roles) && empty($perms) && empty($acls)) return null;
			else return new OrmAuthz($entityClass, $allows, $roles, $perms, $acls, $op_roles, $op_perms, $op_acls);
		} catch (\ReflectionException) {
			return null;
		}
	}

	static protected function parseAllows(DocComment $DocComment, ?string $action): ?array {
		$allows = null;
		$docTag = ($action=='_') ? '': '-'.strtolower($action);
		if ($DocComment->hasTag('authz-allow'.$docTag.'-roles')) {
			$tag = $DocComment->getTag('authz-allow'.$docTag.'-roles');
			foreach ($tag as $k => $v) {
				$allows['roles'][] = $k;
			}
		}
		if ($DocComment->hasTag('authz-allow'.$docTag.'-permissions')) {
			$tag = $DocComment->getTag('authz-allow'.$docTag.'-permissions');
			foreach ($tag as $k => $v) {
				$allows['permissions'][] = $k;
			}
		}
		return $allows;
	}

	static protected function parseRoles(DocComment $DocComment, ?string $action): array {
		$roles = $op = null;
		$docTag = ($action=='_') ? '': '-'.strtolower($action);
		if ($DocComment->hasTag('authz'.$docTag.'-role')) {
			$op = OrmAuthz::OP_ONE;
			$tag = $DocComment->getTag('authz'.$docTag.'-role');
			foreach ($tag as $k => $v) {
				$roles[] = $k;
			}
		}
		if ($DocComment->hasTag('authz'.$docTag.'-roles-all')) {
			$op = OrmAuthz::OP_ALL;
			$tag = $DocComment->getTag('authz'.$docTag.'-roles-all');
			foreach ($tag as $k => $v) {
				$roles[] = $k;
			}
		}
		if ($DocComment->hasTag('authz'.$docTag.'-roles-any')) {
			$op = OrmAuthz::OP_ANY;
			$tag = $DocComment->getTag('authz'.$docTag.'-roles-any');
			foreach ($tag as $k => $v) {
				$roles[] = $k;
			}
		}
		return [$roles, $op];
	}

	static protected function parsePermissions(DocComment $DocComment, ?string $action): array {
		$perms = $op = null;
		$docTag = ($action=='_') ? '': '-'.strtolower($action);
		if($DocComment->hasTag('authz'.$docTag.'-permission')) {
			$op = OrmAuthz::OP_ONE;
			$tag = $DocComment->getTag('authz'.$docTag.'-permission');
			foreach ($tag as $k => $v) {
				$perms[] = $k;
			}
		}
		if($DocComment->hasTag('authz'.$docTag.'-permissions-all')) {
			$op = OrmAuthz::OP_ALL;
			$tag = $DocComment->getTag('authz'.$docTag.'-permissions-all');
			foreach ($tag as $k => $v) {
				$perms[] = $k;
			}
		}
		if($DocComment->hasTag('authz'.$docTag.'-permissions-any')) {
			$op = OrmAuthz::OP_ANY;
			$tag = $DocComment->getTag('authz'.$docTag.'-permissions-any');
			foreach ($tag as $k => $v) {
				$perms[] = $k;
			}
		}
		return [$perms, $op];
	}

	static protected function parseAcls(DocComment $DocComment, ?string $action): array {
		$acls = $op = null;
		$docTag = ($action=='_') ? '': '-'.strtolower($action);
		if($DocComment->hasTag('authz'.$docTag.'-acl')) {
			$op = OrmAuthz::OP_ONE;
			$tag = $DocComment->getTag('authz'.$docTag.'-acl');
			foreach ($tag as $k => $v) {
				$acls[$k] = $v;
			}
		}
		if($DocComment->hasTag('authz'.$docTag.'-acl-all')) {
			$op = OrmAuthz::OP_ALL;
			$tag = $DocComment->getTag('authz'.$docTag.'-acl-all');
			foreach ($tag as $k => $v) {
				$acls[$k] = $v;
			}
		}
		if($DocComment->hasTag('authz'.$docTag.'-acl-any')) {
			$op = OrmAuthz::OP_ANY;
			$tag = $DocComment->getTag('authz'.$docTag.'-acl-any');
			foreach ($tag as $k => $v) {
				$acls[$k] = $v;
			}
		}
		return [$acls, $op];
	}
}
