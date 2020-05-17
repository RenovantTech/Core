<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\auth;
/**
 * Authentication data.
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
class Auth {

	static protected ?Auth $_ = null;

	/** User custom data */
	protected array $data = [];
	/** Group ID
	 * @var integer|null */
	protected $GID = null;
	/** Group name
	 * @var string|null */
	protected $GROUP = null;
	/** User name (full-name)
	 * @var string|null */
	protected $NAME = null;
	/** User ID
	 * @var integer|null */
	protected $UID = null;

	/**
	 * Initialize Auth (static constructor)
	 * @param array $data
	 * @return Auth
	 * @throws AuthException
	 */
	static function init(array $data=[]): Auth {
		if(self::$_) throw new AuthException(1);
		return self::$_ = new Auth($data);
	}

	/**
	 * @return Auth
	 */
	static function instance(): Auth {
		if(is_null(self::$_)) self::$_ = new Auth([]);
		return self::$_;
	}

	/**
	 * @internal
	 */
	static function erase(): void {
		self::$_ = null;
	}

	/**
	 * Set Auth data, also special values GID, GROUP, NAME, UID
	 * @param array $data
	 */
	private function __construct(array $data) {
		foreach ($data as $k=>$v) {
			switch ($k) {
				case 'GID': $this->GID = (integer) $v; break;
				case 'GROUP': $this->GROUP = (string) $v; break;
				case 'NAME': $this->NAME = (string) $v; break;
				case 'UID': $this->UID = (integer) $v; break;
				default: $this->data[$k] = $v;
			}
		}
	}

	/**
	 * Get User data, whole set or single key
	 * @param string|null $key
	 * @return array|mixed|null
	 */
	function data($key=null) {
		return (is_null($key)) ? $this->data : ($this->data[$key] ?? null);
	}

	/**
	 * Get group ID
	 * @return integer|null
	 */
	function GID() {
		return $this->GID;
	}

	/**
	 * Get group name
	 * @return string|null
	 */
	function GROUP() {
		return $this->GROUP;
	}

	/**
	 * Get User name
	 * @return string|null
	 */
	function NAME() {
		return $this->NAME;
	}

	/**
	 * Get User ID
	 * @return integer|null
	 */
	function UID() {
		return $this->UID;
	}
}
