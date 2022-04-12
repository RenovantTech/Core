<?php
namespace renovant\core\authz;
use renovant\core\util\reflection\ReflectionClass,
	renovant\core\util\reflection\ReflectionObject;
/**
 * @internal
 */
class Parser {

	/**
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	static function parse($Obj) {
		$RefClass = new ReflectionClass($Obj);
		$RefObj = new ReflectionObject($Obj);
		$_authz = [];

		// class annotations
		$DocComment = $RefClass->getDocComment();
		if($data = self::_parse($DocComment)) {
			$_authz['_'] = $data;
		}

		// methods annotations
		$refMethods = $RefClass->getMethods();
		foreach($refMethods as $RefMethod) {
			$methodName = $RefMethod->getName();
			$DocComment = $RefMethod->getDocComment();
			if($data = self::_parse($DocComment)) {
				$_authz[$methodName] = $data;
			}
		}

		$RefObj->setProperty('_authz', $_authz, $Obj);
	}

	static protected function _parse($DocComment): ?array {
		$data = null;

		// === RBAC roles ========================================

		if($DocComment->hasTag('authz-role')) {
			$data['roles_op'] = 'ONE';
			$tag = $DocComment->getTag('authz-role');
			foreach ($tag as $k => $v) {
				$data['roles'][] = $k;
			}
		}
		if($DocComment->hasTag('authz-roles-all')) {
			$data['roles_op'] = 'ALL';
			$tag = $DocComment->getTag('authz-roles-all');
			foreach ($tag as $k => $v) {
				$data['roles'][] = $k;
			}
		}
		if($DocComment->hasTag('authz-roles-any')) {
			$data['roles_op'] = 'ANY';
			$tag = $DocComment->getTag('authz-roles-any');
			foreach ($tag as $k => $v) {
				$data['roles'][] = $k;
			}
		}

		// === RBAC permissions ==================================

		if($DocComment->hasTag('authz-permission')) {
			$data['permissions_op'] = 'ONE';
			$tag = $DocComment->getTag('authz-permission');
			foreach ($tag as $k => $v) {
				$data['permissions'][] = $k;
			}
		}
		if($DocComment->hasTag('authz-permissions-all')) {
			$data['permissions_op'] = 'ALL';
			$tag = $DocComment->getTag('authz-permissions-all');
			foreach ($tag as $k => $v) {
				$data['permissions'][] = $k;
			}
		}
		if($DocComment->hasTag('authz-permissions-any')) {
			$data['permissions_op'] = 'ANY';
			$tag = $DocComment->getTag('authz-permissions-any');
			foreach ($tag as $k => $v) {
				$data['permissions'][] = $k;
			}
		}

		// === ACL ==================================

		if($DocComment->hasTag('authz-acl')) {
			$data['acl_op'] = 'ONE';
			$tag = $DocComment->getTag('authz-acl');
			foreach ($tag as $k => $v) {
				$data['acl'][$k] = $v;
			}
		}
		if($DocComment->hasTag('authz-acl-all')) {
			$data['acl_op'] = 'ALL';
			$tag = $DocComment->getTag('authz-acl-all');
			foreach ($tag as $k => $v) {
				$data['acl'][$k] = $v;
			}
		}
		if($DocComment->hasTag('authz-acl-any')) {
			$data['acl_op'] = 'ANY';
			$tag = $DocComment->getTag('authz-acl-any');
			foreach ($tag as $k => $v) {
				$data['acl'][$k] = $v;
			}
		}

		return $data;
	}
}
