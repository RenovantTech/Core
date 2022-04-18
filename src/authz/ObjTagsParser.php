<?php
namespace renovant\core\authz;
use renovant\core\util\reflection\ReflectionClass,
	renovant\core\util\reflection\ReflectionObject;
/**
 * @internal
 */
class ObjTagsParser {

	/**
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	static function parse(object $Obj): ObjAuthz {
		$RefClass = new ReflectionClass($Obj);
		$roles = $perms = $acls = $op_roles = $op_perms = $op_acls = null;

		// class annotations
		$DocComment = $RefClass->getDocComment();
		list($r, $op) = self::parseRoles($DocComment);
		if(!empty($r)) {
			$roles['_'] = $r;
			$op_roles['_'] = $op;
		}
		list($p, $op) = self::parsePermissions($DocComment);
		if(!empty($p)) {
			$perms['_'] = $p;
			$op_perms['_'] = $op;
		}
		list($a, $op) = self::parseAcls($DocComment);
		if(!empty($a)) {
			$acls['_'] = $a;
			$op_acls['_'] = $op;
		}

		// methods annotations
		foreach($RefClass->getMethods() as $RefMethod) {
			$methodName = $RefMethod->getName();
			$DocComment = $RefMethod->getDocComment();
			list($r, $op) = self::parseRoles($DocComment);
			if(!empty($r)) {
				$roles[$methodName] = $r;
				$op_roles[$methodName] = $op;
			}
			list($p, $op) = self::parsePermissions($DocComment);
			if(!empty($p)) {
				$perms[$methodName] = $p;
				$op_perms[$methodName] = $op;
			}
			list($a, $op) = self::parseAcls($DocComment);
			if(!empty($a)) {
				$acls[$methodName] = $a;
				$op_acls[$methodName] = $op;
			}
		}

		// methods params
		$methodsParams = null;
		foreach($RefClass->getMethods() as $RefMethod) {
			$methodName = $RefMethod->getName();
			foreach($RefMethod->getParameters() as $i => $RefParam) {
				$parmaName = $RefParam->getName();
				$methodsParams[$methodName][$parmaName]['index'] = $i;
				$methodsParams[$methodName][$parmaName]['class'] = ($RefParam->getType() && !$RefParam->getType()->isBuiltin()) ? (new ReflectionClass($RefParam->getType()->getName()))->getName() : null;
				$methodsParams[$methodName][$parmaName]['type'] = ($RefParam->getType() && $RefParam->getType()->isBuiltin()) ? $RefParam->getType()->getName() : null;
				$methodsParams[$methodName][$parmaName]['default'] = $RefParam->isDefaultValueAvailable() ? $RefParam->getDefaultValue() : null;
			}
		}

		$RProp = (new ReflectionObject($Obj))->getProperty('_');
		$RProp->setAccessible(true);
		$id = $RProp->getValue($Obj);
		return new ObjAuthz($id, $methodsParams, $roles, $perms, $acls, $op_roles, $op_perms, $op_acls);
	}

	static protected function parseRoles($DocComment): array {
		$roles = $op = null;
		if ($DocComment->hasTag('authz-role')) {
			$op = ObjAuthz::OP_ONE;
			$tag = $DocComment->getTag('authz-role');
			foreach ($tag as $k => $v) {
				$roles[] = $k;
			}
		}
		if ($DocComment->hasTag('authz-roles-all')) {
			$op = ObjAuthz::OP_ALL;
			$tag = $DocComment->getTag('authz-roles-all');
			foreach ($tag as $k => $v) {
				$roles[] = $k;
			}
		}
		if ($DocComment->hasTag('authz-roles-any')) {
			$op = ObjAuthz::OP_ANY;
			$tag = $DocComment->getTag('authz-roles-any');
			foreach ($tag as $k => $v) {
				$roles[] = $k;
			}
		}
		return [$roles, $op];
	}

	static protected function parsePermissions($DocComment): array {
		$perms = $op = null;
		if($DocComment->hasTag('authz-permission')) {
			$op = ObjAuthz::OP_ONE;
			$tag = $DocComment->getTag('authz-permission');
			foreach ($tag as $k => $v) {
				$perms[] = $k;
			}
		}
		if($DocComment->hasTag('authz-permissions-all')) {
			$op = ObjAuthz::OP_ALL;
			$tag = $DocComment->getTag('authz-permissions-all');
			foreach ($tag as $k => $v) {
				$perms[] = $k;
			}
		}
		if($DocComment->hasTag('authz-permissions-any')) {
			$op = ObjAuthz::OP_ANY;
			$tag = $DocComment->getTag('authz-permissions-any');
			foreach ($tag as $k => $v) {
				$perms[] = $k;
			}
		}
		return [$perms, $op];
	}

	static protected function parseAcls($DocComment): array {
		$acls = $op = null;
		if($DocComment->hasTag('authz-acl')) {
			$op = ObjAuthz::OP_ONE;
			$tag = $DocComment->getTag('authz-acl');
			foreach ($tag as $k => $v) {
				$acls[$k] = $v;
			}
		}
		if($DocComment->hasTag('authz-acl-all')) {
			$op = ObjAuthz::OP_ALL;
			$tag = $DocComment->getTag('authz-acl-all');
			foreach ($tag as $k => $v) {
				$acls[$k] = $v;
			}
		}
		if($DocComment->hasTag('authz-acl-any')) {
			$op = ObjAuthz::OP_ANY;
			$tag = $DocComment->getTag('authz-acl-any');
			foreach ($tag as $k => $v) {
				$acls[$k] = $v;
			}
		}
		return [$acls, $op];
	}
}
