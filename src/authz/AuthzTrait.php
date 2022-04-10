<?php
namespace renovant\core\authz;
use const renovant\core\trace\T_INFO;
use renovant\core\sys;

trait AuthzTrait {
	use \renovant\core\CoreTrait;

	protected $_authz;

	/** @throws AuthzException */
	function _authz(string $method) {
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
				foreach ($this->_authz[$method]['roles'] as $role) {
					if ($Authz->role($role)) {
						$checked['ROLES'][] = $role;
						continue;
					}
					throw new AuthzException($exCode, [$role, $this->_, $method]);
				}
				break;
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
				foreach ($this->_authz[$method]['permissions'] as $role) {
					if ($Authz->permissions($role)) {
						$checked['PERMISSIONS'][] = $role;
						continue;
					}
					throw new AuthzException($exCode, [$role, $this->_, $method]);
				}
				break;
			default:
				foreach ($this->_authz[$method]['permissions'] as $role) {
					if (!$Authz->permissions($role)) throw new AuthzException($exCode, [$role, $this->_, $method]);
					else $checked['PERMISSIONS'][] = $role;
				}
		}
	}
}
