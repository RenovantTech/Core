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
			$roles = $perms = $acls = $op_roles = $op_perms = $op_acls = null;
			$actions = [
				OrmAuthz::ACTION_ALL,
				OrmAuthz::ACTION_INSERT,
				OrmAuthz::ACTION_SELECT,
				OrmAuthz::ACTION_UPDATE,
				OrmAuthz::ACTION_DELETE
			];
			foreach ($actions as $action) {
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
			else return new OrmAuthz($entityClass, $roles, $perms, $acls, $op_roles, $op_perms, $op_acls);
		} catch (\ReflectionException) {
			return null;
		}
	}

	static protected function parseRoles(DocComment $DocComment, ?string $action): array {
		$roles = $op = null;
		$tag = ($action=='_') ? '': '-'.strtolower($action);
		if ($DocComment->hasTag('authz'.$tag.'-role')) {
			$op = OrmAuthz::OP_ONE;
			$tag = $DocComment->getTag('authz'.$tag.'-role');
			foreach ($tag as $k => $v) {
				$roles[] = $k;
			}
		}
		if ($DocComment->hasTag('authz'.$tag.'-roles-all')) {
			$op = OrmAuthz::OP_ALL;
			$tag = $DocComment->getTag('authz'.$tag.'-roles-all');
			foreach ($tag as $k => $v) {
				$roles[] = $k;
			}
		}
		if ($DocComment->hasTag('authz'.$tag.'-roles-any')) {
			$op = OrmAuthz::OP_ANY;
			$tag = $DocComment->getTag('authz'.$tag.'-roles-any');
			foreach ($tag as $k => $v) {
				$roles[] = $k;
			}
		}
		return [$roles, $op];
	}

	static protected function parsePermissions(DocComment $DocComment, ?string $action): array {
		$perms = $op = null;
		$tag = ($action=='_') ? '': '-'.strtolower($action);
		if($DocComment->hasTag('authz'.$tag.'-permission')) {
			$op = OrmAuthz::OP_ONE;
			$tag = $DocComment->getTag('authz'.$tag.'-permission');
			foreach ($tag as $k => $v) {
				$perms[] = $k;
			}
		}
		if($DocComment->hasTag('authz'.$tag.'-permissions-all')) {
			$op = OrmAuthz::OP_ALL;
			$tag = $DocComment->getTag('authz'.$tag.'-permissions-all');
			foreach ($tag as $k => $v) {
				$perms[] = $k;
			}
		}
		if($DocComment->hasTag('authz'.$tag.'-permissions-any')) {
			$op = OrmAuthz::OP_ANY;
			$tag = $DocComment->getTag('authz'.$tag.'-permissions-any');
			foreach ($tag as $k => $v) {
				$perms[] = $k;
			}
		}
		return [$perms, $op];
	}

	static protected function parseAcls(DocComment $DocComment, ?string $action): array {
		$acls = $op = null;
		$tag = ($action=='_') ? '': '-'.strtolower($action);
		if($DocComment->hasTag('authz'.$tag.'-acl')) {
			$op = OrmAuthz::OP_ONE;
			$tag = $DocComment->getTag('authz'.$tag.'-acl');
			foreach ($tag as $k => $v) {
				$acls[$k] = $v;
			}
		}
		if($DocComment->hasTag('authz'.$tag.'-acl-all')) {
			$op = OrmAuthz::OP_ALL;
			$tag = $DocComment->getTag('authz'.$tag.'-acl-all');
			foreach ($tag as $k => $v) {
				$acls[$k] = $v;
			}
		}
		if($DocComment->hasTag('authz'.$tag.'-acl-any')) {
			$op = OrmAuthz::OP_ANY;
			$tag = $DocComment->getTag('authz'.$tag.'-acl-any');
			foreach ($tag as $k => $v) {
				$acls[$k] = $v;
			}
		}
		return [$acls, $op];
	}
}
