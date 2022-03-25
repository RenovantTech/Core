<?php
namespace renovant\core\acl;
use renovant\core\sys;

trait AclTrait {
	use \renovant\core\CoreTrait;

	/** ACL actions */
	protected $_acl_actions;
	/** ACL filters */
	protected $_acl_filters;
	/** ACL roles */
	protected $_acl_roles;

	/** @throws AclException */
	function _acl(string $method) {
		$ACL = sys::acl();
		// check roles
		if(!empty($this->_acl_roles) && isset($this->_acl_roles['_'])) {
			foreach ($this->_acl_roles['_'] as $role)
				if(!$ACL->role($role)) throw new AclException(300, [$role, $this->_]);
		}
		if(!empty($this->_acl_roles) && isset($this->_acl_roles[$method])) {
			foreach ($this->_acl_roles[$method] as $role)
				if(!$ACL->role($role)) throw new AclException(301, [$role, $this->_, $method]);
		}
		// check actions
		if(!empty($this->_acl_actions) && isset($this->_acl_actions['_'])) {
			foreach ($this->_acl_actions['_'] as $action)
				if(!$ACL->action($action)) throw new AclException(100, [$action, $this->_]);
		}
		if(!empty($this->_acl_actions) && isset($this->_acl_actions[$method])) {
			foreach ($this->_acl_actions[$method] as $action)
				if(!$ACL->action($action)) throw new AclException(101, [$action, $this->_, $method]);
		}
		// check filters
		if(!empty($this->_acl_filters) && isset($this->_acl_filters['_'])) {
			foreach ($this->_acl_filters['_'] as $filter)
				if(!$ACL->filter($filter)) throw new AclException(200, [$filter, $this->_]);
		}
		if(!empty($this->_acl_filters) && isset($this->_acl_filters[$method])) {
			foreach ($this->_acl_filters[$method] as $filter)
				if(!$ACL->filter($filter)) throw new AclException(201, [$filter, $this->_, $method]);
		}
	}
}
