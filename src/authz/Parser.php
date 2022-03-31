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

		// class annotations
		$DocComment = $RefClass->getDocComment();
		$actions = $filters = $roles = [];
		if($DocComment->hasTag('authz')) {
			$tag = $DocComment->getTag('authz');
			foreach ($tag as $k => $v) {
				switch ($k) {
					case 'action': $actions['_']=[]; array_push($actions['_'], ...explode(',', str_replace(' ','',$v))); break;
					case 'filter': $filters['_']=[]; array_push($filters['_'], ...explode(',', str_replace(' ','',$v))); break;
					case 'role': $roles['_']=[]; array_push($roles['_'], ...explode(',', str_replace(' ','',$v))); break;
				}
			}
		}
		if($DocComment->hasTag('authz-action')) {
			$tag = $DocComment->getTag('authz-action');
			foreach ($tag as $k => $v) {
				$actions['_'][] = $k;
			}
		}
		if($DocComment->hasTag('authz-filter')) {
			$tag = $DocComment->getTag('authz-filter');
			foreach ($tag as $k => $v) {
				$filters['_'][] = $k;
			}
		}
		if($DocComment->hasTag('authz-role')) {
			$tag = $DocComment->getTag('authz-role');
			foreach ($tag as $k => $v) {
				$roles['_'][] = $k;
			}
		}

		// methods annotations
		$refMethods = $RefClass->getMethods();
		foreach($refMethods as $RefMethod) {
			$methodName = $RefMethod->getName();
			$DocComment = $RefMethod->getDocComment();
			if($DocComment->hasTag('authz')) {
				$tag = $DocComment->getTag('authz');
				foreach ($tag as $k => $v) {
					switch ($k) {
						case 'action': $actions[$methodName]=[]; array_push($actions[$methodName], ...explode(',', str_replace(' ','',$v))); break;
						case 'filter': $filters[$methodName]=[]; array_push($filters[$methodName], ...explode(',', str_replace(' ','',$v))); break;
						case 'role': $roles[$methodName]=[]; array_push($roles[$methodName], ...explode(',', str_replace(' ','',$v))); break;
					}
				}
			}
			if($DocComment->hasTag('authz-action')) {
				$tag = $DocComment->getTag('authz-action');
				foreach ($tag as $k => $v) {
					$actions[$methodName][] = $k;
				}
			}
			if($DocComment->hasTag('authz-filter')) {
				$tag = $DocComment->getTag('authz-filter');
				foreach ($tag as $k => $v) {
					$filters[$methodName][] = $k;
				}
			}
			if($DocComment->hasTag('authz-role')) {
				$tag = $DocComment->getTag('authz-role');
				foreach ($tag as $k => $v) {
					$roles[$methodName][] = $k;
				}
			}
		}

		$RefObj->setProperty('_authz_actions', $actions, $Obj);
		$RefObj->setProperty('_authz_filters', $filters, $Obj);
		$RefObj->setProperty('_authz_roles', $roles, $Obj);
	}
}
