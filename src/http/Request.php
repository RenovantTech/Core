<?php
namespace renovant\core\http;
class Request {

	/** Request named attributes.
	 * @var	array */
	protected $attrs = [];
	/** Request parameters (contents of $_GET, $_POST & $_COOKIE).
	 * @var	array */
	protected $params = [];
	/** Request HTTP headers
	 * @var	array */
	protected $headers = [];
	/** HTTP method
	 * @var string */
	protected $method;
	/** HTTP Request query
	 * @var string */
	protected $QUERY;
	/** POST/PUT raw data
	 * @var string */
	protected $rawData;
	/** HTTP Request URI
	 * @var string */
	protected $URI;

	/**
	 * Constructor: create a new HTTP Request
	 * @param string|null $uri the HTTP URI
	 * @param string|null $method the HTTP method
	 * @param array|null $params the GET/POST parameters
	 * @param array|null $headers
	 * @param string|null $data the raw body data
	 */
	function __construct(string $uri=null, string $method=null, array $params=null, array $headers=null, string $data=null) {
		$this->URI = strstr(($uri ? : $_SERVER['REQUEST_URI']).'?','?',true);
		$this->method = $method ? : $_SERVER['REQUEST_METHOD'];
		// @FIXME avoid memory duplication
		$this->params = $params ? : array_merge($_GET,$_POST);
		$this->rawData = $data ? : file_get_contents('php://input');
		$this->QUERY = $_SERVER['QUERY_STRING'];
		if($headers) {
			foreach($headers as $key=>$value) {
				if(substr($key,0,5)=='HTTP_') $key = substr($key,5);
				$this->headers[strtolower(str_replace('_','-',$key))] = $value;
			}
		} else {
			foreach($_SERVER as $key=>$value) {
				if(substr($key,0,5)!='HTTP_') continue;
				$key = strtolower(str_replace('_','-',substr($key,5)));
				$this->headers[$key]=$value;
			}
		}
		if(isset($this->headers['content-type']) && substr($this->headers['content-type'],0,16)=='application/json') {
			$this->params = array_merge($this->params, (array) json_decode($this->rawData, true));
		}
	}

	/**
	 * Return Request param
	 * @param string $p parameter name
	 * @return mixed|null
	 */
	function get(string $p) {
		return $this->params[$p] ?? null;
	}

	function getAttribute($k) {
		return $this->attrs[$k] ?? null;
	}

	/**
	 * Set Request param
	 * @param string $p parameter name
	 * @param mixed $v parameter value
	 */
	function set(string $p, $v) {
		$this->params[$p]=$v;
	}

	function setAttribute($k, $v) {
		$this->attrs[$k] = $v;
	}

	/**
	 * Return QUERY_STRING
	 * @return string
	 */
	function QUERY(): string {
		return $this->QUERY;
	}

	function URI() {
		return $this->URI;
	}

	/**
	 * @return array GET data
	 */
	function getGetData(): array {
		return $_GET;
	}

	function getHeader($key) {
		$key = strtolower($key);
		return $this->headers[$key] ?? null;
	}

	/**
	 * Get the HTTP method (GET, POST, PUT, ...)
	 * @return string HTTP method
	 */
	function getMethod(): string {
		return $this->method;
	}

	function getJsonData(): array {
		return json_decode($this->rawData, true);
	}

	/**
	 * @return array POST data
	 */
	function getPostData(): array {
		return $_POST;
	}

	/**
	 * @return false|string|null POST data
	 */
	function getPutData() {
		return ($this->method == 'PUT') ? $this->rawData : null;
	}

	function getRawData() {
		return $this->rawData;
	}

	/**
	 * @return boolean TRUE if Request method = GET
	 */
	function isGet(): bool {
		return ($this->method=='GET');
	}

	/**
	 * @return boolean TRUE if Request method = GET
	 */
	function isPost(): bool {
		return ($this->method=='POST');
	}
}
