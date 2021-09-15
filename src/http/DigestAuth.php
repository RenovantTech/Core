<?php
namespace renovant\core\http;
/**
 * HTTP Digest Authentication.
 */
class DigestAuth {

	/** Realm
	 * @var string */
	protected $realm;
	/** A1 callback function
	 * @var callback */
	protected $A1Function;
	/** Digest data
	 * @var array */
	protected $data = [];

	/**
	 * Requires REALM and A1 function.
	 * A1 is an hash, calculated as: md5(username:realm:password)
	 * @param string $realm
	 * @param callback $A1Function A1 function, must return md5(username:realm:password)
	 */
	function __construct($realm, $A1Function) {
		$this->realm = (string) $realm;
		if(!is_callable($A1Function)) trigger_error('invalid A1 function');
		$this->A1Function = $A1Function;
	}

	/**
	 * Check HTTP Digest
	 * @return bool TRUE on success
	 */
	function checkDigest() {
		$data = $this->data;
		$A1 = call_user_func($this->A1Function, $data['username']);
		$A2 = md5($_SERVER['REQUEST_METHOD'].':'.$data['uri']);
		$valid_response = md5($A1.':'.$data['nonce'].':'.$data['nc'].':'.$data['cnonce'].':'.$data['qop'].':'.$A2);
		return ($data['response'] === $valid_response);
	}

	function sendAuthHeaders() {
		header('HTTP/1.1 401 Unauthorized');
		header(sprintf('WWW-Authenticate: Digest realm="%s",qop="auth",nonce="%s",opaque="%s"', $this->realm, uniqid(), md5($this->realm)));
	}

	/**
	 * Parse HTTP Digest, if any.
	 * @return bool TRUE on success
	 */
	function parseDigest() {
		$missing = ['nonce'=>1,'nc'=>1,'cnonce'=>1,'qop'=>1,'username'=>1,'uri'=>1,'response'=>1];
		preg_match_all('@(\w+)=(?:([\'])([^\']+)(?:\2)|(["])([^"]+)(?:\4)|(\w+)),?@', $_SERVER['PHP_AUTH_DIGEST'], $matches, PREG_SET_ORDER);
		foreach($matches as $m) {
			$this->data[$m[1]] = isset($m[6]) ? $m[6] : (isset($m[5]) ? $m[5] : $m[3]);
			unset($missing[$m[1]]);
		}
		return $missing ? false : true;
	}
}
