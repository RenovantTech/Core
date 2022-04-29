<?php
namespace renovant\core\authz;
use const renovant\core\trace\T_INFO;
use renovant\core\sys,
	renovant\core\db\orm\OrmEvent;

class OrmAuthz {

	const CACHE_TAG = 'orm:authz';

	const OP_ALLOW = 'ALLOW';
	const OP_ONE = 'ONE';
	const OP_ALL = 'ALL';
	const OP_ANY = 'ANY';

	const ACTION_ALL	= '_';
	const ACTION_INSERT	= 'INSERT';
	const ACTION_SELECT	= 'SELECT';
	const ACTION_UPDATE	= 'UPDATE';
	const ACTION_DELETE	= 'DELETE';

	/** OID (Object Identifier) */
	protected string $_;

	protected ?array $allows;
	protected ?array $roles;
	protected ?array $perms;
	protected ?array $acls;

	protected ?array $op_roles;
	protected ?array $op_perms;
	protected ?array $op_acls;

	function __construct(string $entityClass, ?array $allows, ?array $roles, ?array $perms, ?array $acls, ?array $op_roles, ?array $op_perms, ?array $op_acls) {
		$this->_ = $entityClass;
		$this->allows = $allows;
		$this->roles = $roles;
		$this->perms = $perms;
		$this->acls = $acls;
		$this->op_roles = $op_roles;
		$this->op_perms = $op_perms;
		$this->op_acls = $op_acls;
	}

	/** @throws AuthzException */
	function check(string $action, OrmEvent $OrmEvent): void {
		$Authz = sys::authz();
		$checked = [];
		try {
			// check ALLOWS
			if(
				(isset($this->allows[self::ACTION_ALL]) && $this->checkAllows($Authz, $checked, self::ACTION_ALL))
				||
				(isset($this->allows[$action]) && $this->checkAllows($Authz, $checked, $action))
			) sys::trace(LOG_DEBUG, T_INFO, '[AUTHZ] allow OK', $checked);
			else {
				// check RBAC roles
				if(isset($this->roles[self::ACTION_ALL]))
					$this->checkRoles($Authz, $checked);
				if(isset($this->roles[$action]))
					$this->checkRoles($Authz, $checked, $action);

				// check RBAC permissions
				if(isset($this->perms[self::ACTION_ALL]))
					$this->checkPermissions($Authz, $checked);
				if(isset($this->perms[$action]))
					$this->checkPermissions($Authz, $checked, $action);

				// check ACL
				if(isset($this->acls[self::ACTION_ALL]))
					$this->checkAcls($Authz, $checked, null, $OrmEvent);
				if(isset($this->acls[$action]))
					$this->checkAcls($Authz, $checked, $action, $OrmEvent);

				if(empty($checked)) sys::trace(LOG_DEBUG, T_INFO, '[AUTHZ] empty checks');
				else sys::trace(LOG_DEBUG, T_INFO, '[AUTHZ] check OK', $checked);
			}
		} catch (AuthzException $Ex) {
			sys::trace(LOG_WARNING, T_INFO, '[AUTHZ] check FAILED');
			throw $Ex;
		}
	}

	protected function checkAllows(Authz $Authz, array &$checked, string $action): bool {
		if(isset($this->allows[$action]['roles']))
			foreach ($this->allows[$action]['roles'] as $role) {
				if ($Authz->role($role)) {
					$checked['ROLES'][] = $role;
					return true;
				}
			}
		if(isset($this->allows[$action]['permissions']))
			foreach ($this->allows[$action]['permissions'] as $perm) {
				if ($Authz->permission($perm)) {
					$checked['PERMISSIONS'][] = $perm;
					return true;
				}
			}
		return false;
	}

	/** @throws AuthzException */
	protected function checkRoles(Authz $Authz, array &$checked, ?string $action=null): void {
		$exCode = $action ? 301 : 300;
		$action = $action ?? self::ACTION_ALL;
		switch ($this->op_roles[$action]) {
			case self::OP_ANY:
				$exRoles = [];
				foreach ($this->roles[$action] as $role) {
					if ($Authz->role($role)) {
						$checked['ROLES'][] = $role;
						break 2;
					}
					$exRoles[] = $role;
				}
				throw new AuthzException($exCode, [implode(', ', $exRoles), $this->_, $action]);
			default:
				foreach ($this->roles[$action] as $role) {
					if (!$Authz->role($role)) throw new AuthzException($exCode, [$role, $this->_, $action]);
					else $checked['ROLES'][] = $role;
				}
		}
	}

	/** @throws AuthzException */
	protected function checkPermissions(Authz $Authz, array &$checked, ?string $action=null): void {
		$exCode = $action ? 401 : 400;
		$action = $action ?? self::ACTION_ALL;
		switch ($this->op_perms[$action]) {
			case self::OP_ANY:
				$exPerms = [];
				foreach ($this->perms[$action] as $perm) {
					if ($Authz->permission($perm)) {
						$checked['PERMISSIONS'][] = $perm;
						break 2;
					}
					$exPerms[] = $perm;
				}
				throw new AuthzException($exCode, [implode(', ', $exPerms), $this->_, $action]);
			default:
				foreach ($this->perms[$action] as $perm) {
					if (!$Authz->permission($perm)) throw new AuthzException($exCode, [$perm, $this->_, $action]);
					else $checked['PERMISSIONS'][] = $perm;
				}
		}
	}

	/** @throws AuthzException */
	protected function checkAcls(Authz $Authz, array &$checked, ?string $actionName, OrmEvent $OrmEvent): void {
		$exCode = $actionName ? 101 : 100;
		$action = $actionName ?? self::ACTION_ALL;
		if(!isset($this->acls[$action])) return;
		switch ($this->op_acls[$action]) {
			default:
				foreach ($this->acls[$action] as $aclKey => $aclProp) {
					if($values = $Authz->aclValues($aclKey)) {
						$criteriaExp = $aclProp.',IN,'.implode(',', $values);
						sys::trace(LOG_DEBUG, T_INFO, '[AUTHZ] ACL "'.$aclKey.'": add $criteriaExp '.$criteriaExp);
						$OrmEvent->criteriaExp($criteriaExp);
						$checked['ACL'][] = $aclKey;
					} else throw new AuthzException($exCode, [$aclKey, $this->_, $action]);
				}
		}
	}
}
