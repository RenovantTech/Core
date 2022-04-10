<?php
namespace renovant\core\authz;
use const renovant\core\trace\T_INFO;
use renovant\core\sys;

trait AuthzTrait {
	use \renovant\core\CoreTrait;

	protected $_authz;

	/** AUTHZ actions */
	protected $_authz_actions;
	/** AUTHZ filters */
	protected $_authz_filters;
	/** AUTHZ roles */
	protected $_authz_roles;

	/** @throws AuthzException */
	function _authz(string $method) {
		$Authz = sys::authz();
		$checked = [];
		try {
			// check roles
			if(isset($this->_authz['_']['roles']))
				$this->_auth_roles($Authz, $checked);
			if(isset($this->_authz[$method]['roles']))
				$this->_auth_roles($Authz, $checked, $method);

			// check actions
			if(!empty($this->_authz_actions) && isset($this->_authz_actions['_'])) {
				foreach ($this->_authz_actions['_'] as $action)
					if(!$Authz->action($action)) throw new AuthzException(100, [$action, $this->_]);
					else $checked['ACTIONS'][] = $action;
			}
			if(!empty($this->_authz_actions) && isset($this->_authz_actions[$method])) {
				foreach ($this->_authz_actions[$method] as $action)
					if(!$Authz->action($action)) throw new AuthzException(101, [$action, $this->_, $method]);
					else $checked['ACTIONS'][] = $action;
			}
			// check filters
			if(!empty($this->_authz_filters) && isset($this->_authz_filters['_'])) {
				foreach ($this->_authz_filters['_'] as $filter)
					if(!$Authz->filter($filter)) throw new AuthzException(200, [$filter, $this->_]);
					else $checked['FILTERS'][] = $filter;
			}
			if(!empty($this->_authz_filters) && isset($this->_authz_filters[$method])) {
				foreach ($this->_authz_filters[$method] as $filter)
					if(!$Authz->filter($filter)) throw new AuthzException(201, [$filter, $this->_, $method]);
					else $checked['FILTERS'][] = $filter;
			}
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
}
