<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\http;
/**
 * HTTP Request.
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
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
	 * @param string $uri the HTTP URI
	 * @param string $method the HTTP method
	 * @param array $params the GET/POST parameters
	 * @param array $headers
	 * @param string $data the raw body data
	 */
	function __construct($uri=null, $method=null, array $params=null, array $headers=null, $data=null) {
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
			$this->params = array_merge($this->params, (array) json_decode($this->rawData));
		}
	}

	/**
	 * Return Request param
	 * @param string $p parameter name
	 * @return mixed|null
	 */
	function get($p) {
		return isset($this->params[$p]) ? $this->params[$p] : null;
	}

	function getAttribute($k) {
		return isset($this->attrs[$k]) ? $this->attrs[$k] : null;
	}

	/**
	 * Set Request param
	 * @param string $p parameter name
	 * @param mixed $v parameter value
	 */
	function set($p, $v) {
		$this->params[$p]=$v;
	}

	function setAttribute($k, $v) {
		$this->attrs[$k] = $v;
	}

	/**
	 * Return QUERY_STRING
	 * @return string
	 */
	function QUERY() {
		return $this->QUERY;
	}

	function URI() {
		return $this->URI;
	}

	/**
	 * @return array GET data
	 */
	function getGetData() {
		return $_GET;
	}

	function getHeader($key) {
		$key = strtolower($key);
		return isset($this->headers[$key]) ? $this->headers[$key] : null;
	}

	/**
	 * Get the HTTP method (GET, POST, PUT, ...)
	 * @return string HTTP method
	 */
	function getMethod() {
		return $this->method;
	}

	/**
	 * @return array POST data
	 */
	function getPostData() {
		return $_POST;
	}

	/**
	 * @return array POST data
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
	function isGet() {
		return ($this->method=='GET');
	}

	/**
	 * @return boolean TRUE if Request method = GET
	 */
	function isPost() {
		return ($this->method=='POST');
	}
}
