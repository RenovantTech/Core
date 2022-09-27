<?php
namespace renovant\core\auth;
class Auth {

	const EVENT_LOGIN = 'auth:login';

	/** User custom data */
	protected array $data = [];
	/** Group ID */
	protected ?int $GID = null;
	/** Group name */
	protected ?string $GROUP = null;
	/** Username (full-name) */
	protected ?string $NAME = null;
	/** User ID */
	protected ?int $UID = null;

	static function instance(): Auth {
		static $Auth;
		if(!isset($Auth)) $Auth = new Auth;
		return $Auth;
	}

	private function __construct(?int $UID=null, ?int $GID=null, ?string $name=null, ?string $group=null, array $data=[]) {
		$this->GID = $GID;
		$this->GROUP = $group;
		$this->NAME = $name;
		$this->UID = $UID;
		$this->data = $data;
	}

	/**
	 * Get User data, whole set or single key
	 * @param string|null $key
	 * @return array|mixed|null
	 */
	function data(?string $key=null) {
		return (is_null($key)) ? $this->data : ($this->data[$key] ?? null);
	}

	/** Get group ID */
	function GID(): ?int {
		return $this->GID;
	}

	/** Get group name */
	function GROUP(): ?string {
		return $this->GROUP;
	}

	/** Get Username */
	function NAME(): ?string {
		return $this->NAME;
	}

	/** Get User ID */
	function UID(): ?int {
		return $this->UID;
	}
}
