<?php
namespace renovant\core\authz;
use const renovant\core\trace\T_INFO;
use renovant\core\sys;

class ObjAuthz {

	const CACHE_SUFFIX = ':authz';

	const OP_ONE = 1;
	const OP_ALL = 2;
	const OP_ANY = 3;

	/** OID (Object Identifier) */
	protected string $_;
	protected ?array $methodsParams;

	protected ?array $roles;
	protected ?array $perms;
	protected ?array $acls;

	protected ?array $op_roles;
	protected ?array $op_perms;
	protected ?array $op_acls;

	function __construct($id, $methodsParams, $roles, $perms, $acls, $op_roles, $op_perms, $op_acls) {
		$this->_ = $id;
		$this->methodsParams = $methodsParams;
		$this->roles = $roles;
		$this->perms = $perms;
		$this->acls = $acls;
		$this->op_roles = $op_roles;
		$this->op_perms = $op_perms;
		$this->op_acls = $op_acls;

	}

	/** @throws AuthzException */
	function check(string $method, $args): void {
		$Authz = sys::authz();
		$checked = [];
		try {
			// check RBAC roles
			if(isset($this->roles['_']))
				$this->checkRoles($Authz, $checked);
			if(isset($this->roles[$method]))
				$this->checkRoles($Authz, $checked, $method);

			// check RBAC permissions
			if(isset($this->perms['_']))
				$this->checkPermissions($Authz, $checked);
			if(isset($this->perms[$method]))
				$this->checkPermissions($Authz, $checked, $method);

			// check ACL
			if(isset($this->acls['_']) || isset($this->acls[$method]))
				$this->checkAcls($Authz, $checked, $method, $args);

			if(empty($checked)) sys::trace(LOG_DEBUG, T_INFO, '[AUTHZ] empty checks');
			else sys::trace(LOG_DEBUG, T_INFO, '[AUTHZ] check OK', $checked);
		} catch (AuthzException $Ex) {
			sys::trace(LOG_WARNING, T_INFO, '[AUTHZ] check FAILED');
			throw $Ex;
		}
	}

	/** @throws AuthzException */
	protected function checkRoles(Authz $Authz, array &$checked, ?string $method=null): void {
		$exCode = $method ? 301 : 300;
		$method = $method ?? '_';
		switch ($this->op_roles[$method]) {
			case self::OP_ANY:
				$exRoles = [];
				foreach ($this->roles[$method] as $role) {
					if ($Authz->role($role)) {
						$checked['ROLES'][] = $role;
						break 2;
					}
					$exRoles[] = $role;
				}
				throw new AuthzException($exCode, [implode(', ', $exRoles), $this->_, $method]);
			default:
				foreach ($this->roles[$method] as $role) {
					if (!$Authz->role($role)) throw new AuthzException($exCode, [$role, $this->_, $method]);
					else $checked['ROLES'][] = $role;
				}
		}
	}

	/** @throws AuthzException */
	protected function checkPermissions(Authz $Authz, array &$checked, ?string $method=null): void {
		$exCode = $method ? 401 : 400;
		$method = $method ?? '_';
		switch ($this->op_perms[$method]) {
			case self::OP_ANY:
				$exPerms = [];
				foreach ($this->perms[$method] as $perm) {
					if ($Authz->permission($perm)) {
						$checked['PERMISSIONS'][] = $perm;
						break 2;
					}
					$exPerms[] = $perm;
				}
				throw new AuthzException($exCode, [implode(', ', $exPerms), $this->_, $method]);
			default:
				foreach ($this->perms[$method] as $perm) {
					if (!$Authz->permission($perm)) throw new AuthzException($exCode, [$perm, $this->_, $method]);
					else $checked['PERMISSIONS'][] = $perm;
				}
		}
	}

	/** @throws AuthzException */
	protected function checkAcls(Authz $Authz, array &$checked, ?string $methodName, $args): void {
		$exCode = $methodName ? 101 : 100;
		$method = $methodName ?? '_';
		switch ($this->op_acls[$method]) {
			case self::OP_ANY:
				$exKeys = [];
				foreach ($this->acls[$method] as $aclKey => $aclParam) {
					$index = $this->methodsParams[$methodName][substr($aclParam, 1)]['index'];
					if ($Authz->acl($aclKey, $args[$index])) {
						$checked['ACL'][] = $aclKey;
						break 2;
					}
					$exKeys[] = $aclKey;
				}
				throw new AuthzException($exCode, [implode(', ', $exKeys), $this->_, $method]);
			default:
				foreach ($this->acls[$method] as $aclKey => $aclParam) {
					$index = $this->methodsParams[$methodName][substr($aclParam, 1)]['index'];
					if (!$Authz->acl($aclKey, $args[$index])) throw new AuthzException($exCode, [$aclKey, $this->_, $method]);
					else $checked['ACL'][] = $aclKey;
				}
		}
	}
}
