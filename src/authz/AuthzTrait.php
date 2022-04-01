<?php
namespace renovant\core\authz;
use const renovant\core\trace\T_INFO;
use renovant\core\sys;

trait AuthzTrait {
	use \renovant\core\CoreTrait;

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
			if(!empty($this->_authz_roles) && isset($this->_authz_roles['_'])) {
				foreach ($this->_authz_roles['_'] as $role)
					if(!$Authz->role($role)) throw new AuthzException(300, [$role, $this->_]);
					else $checked['ROLES'][] = $role;
			}
			if(!empty($this->_authz_roles) && isset($this->_authz_roles[$method])) {
				foreach ($this->_authz_roles[$method] as $role)
					if(!$Authz->role($role)) throw new AuthzException(301, [$role, $this->_, $method]);
					else $checked['ROLES'][] = $role;
			}
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
}
