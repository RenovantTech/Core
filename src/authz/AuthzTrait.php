<?php
namespace renovant\core\authz;
use const renovant\core\trace\T_INFO;
use renovant\core\sys;

trait AuthzTrait {
	use \renovant\core\CoreTrait;

	protected $_authz;

	/** @throws AuthzException|\ReflectionException */
	function _authz(string $method, $args) {
		$Authz = sys::authz();
		$checked = [];
		try {
			// check RBAC roles
			if(isset($this->_authz['_']['roles']))
				$this->_auth_roles($Authz, $checked);
			if(isset($this->_authz[$method]['roles']))
				$this->_auth_roles($Authz, $checked, $method);

			// check RBAC permissions
			if(isset($this->_authz['_']['permissions']))
				$this->_auth_permissions($Authz, $checked);
			if(isset($this->_authz[$method]['permissions']))
				$this->_auth_permissions($Authz, $checked, $method);

			// check ACL
			if(isset($this->_authz['_']['acl']) || isset($this->_authz[$method]['acl']))
				$this->_auth_acl($Authz, $checked, $method, $args);


			sys::trace(LOG_DEBUG, T_INFO, '[AUTHZ] check OK', $checked);
		} catch (AuthzException $Ex) {
			sys::trace(LOG_WARNING, T_INFO, '[AUTHZ] check FAILED');
			throw $Ex;
		}
	}

	/** @throws AuthzException */
	protected function _auth_roles($Authz, &$checked, $method=null) {
		$exCode = $method ? 301 : 300;
		$method = $method ?? '_';
		switch ($this->_authz[$method]['roles_op']) {
			case 'ANY':
				$exRoles = [];
				foreach ($this->_authz[$method]['roles'] as $role) {
					if ($Authz->role($role)) {
						$checked['ROLES'][] = $role;
						break 2;
					}
					$exRoles[] = $role;
				}
				throw new AuthzException($exCode, [implode(', ', $exRoles), $this->_, $method]);
			default:
				foreach ($this->_authz[$method]['roles'] as $role) {
					if (!$Authz->role($role)) throw new AuthzException($exCode, [$role, $this->_, $method]);
					else $checked['ROLES'][] = $role;
				}
		}
	}

	/** @throws AuthzException */
	protected function _auth_permissions($Authz, &$checked, $method=null) {
		$exCode = $method ? 401 : 400;
		$method = $method ?? '_';
		switch ($this->_authz[$method]['permissions_op']) {
			case 'ANY':
				$exPerms = [];
				foreach ($this->_authz[$method]['permissions'] as $perm) {
					if ($Authz->permissions($perm)) {
						$checked['PERMISSIONS'][] = $perm;
						break 2;
					}
					$exPerms[] = $perm;
				}
				throw new AuthzException($exCode, [implode(', ', $exPerms), $this->_, $method]);
			default:
				foreach ($this->_authz[$method]['permissions'] as $perm) {
					if (!$Authz->permissions($perm)) throw new AuthzException($exCode, [$perm, $this->_, $method]);
					else $checked['PERMISSIONS'][] = $perm;
				}
		}
	}

	/** @throws AuthzException|\ReflectionException */
	protected function _auth_acl($Authz, &$checked, $methodName, $args) {

		$params = [];
		$RefMethod = new \ReflectionMethod($this, $methodName);
		foreach($RefMethod->getParameters() as $i => $RefParam) {
			$name = $RefParam->getName();
			$params[$name]['index'] = $i;
			$params[$name]['class'] = !is_null($RefParam->getClass()) ? $RefParam->getClass()->getName() : null;
			$params[$name]['type'] = $RefParam->getType();
			$params[$name]['default'] = $RefParam->isDefaultValueAvailable() ? $RefParam->getDefaultValue() : null;
		}

		$exCode = $methodName ? 101 : 100;
		$method = $methodName ?? '_';
		switch ($this->_authz[$method]['acl_op']) {
			case 'ANY':
				$exKeys = [];
				foreach ($this->_authz[$method]['acl'] as $aclKey => $aclParam) {
					$index = $params[substr($aclParam, 1)]['index'];
					if ($Authz->acl($aclKey, $args[$index])) {
						$checked['ACL'][] = $aclKey;
						break 2;
					}
					$exKeys[] = $aclKey;
				}
				throw new AuthzException($exCode, [implode(', ', $exKeys), $this->_, $method]);
			default:
				foreach ($this->_authz[$method]['acl'] as $aclKey => $aclParam) {
					$index = $params[substr($aclParam, 1)]['index'];
					if (!$Authz->acl($aclKey, $args[$index])) throw new AuthzException($exCode, [$aclKey, $this->_, $method]);
					else $checked['ACL'][] = $aclKey;
				}
		}
	}
}
