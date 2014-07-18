<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\http;
/**
 * HTTP Request.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Request {

	/** Request named attributes.
	 * @var	array */
	protected $attrs = [];
	/** Request parameters (contents of $_GET, $_POST & $_COOKIE).
	 * @var	array */
	protected $data = [];
	/** Request HTTP headers
	 * @var	array */
	protected $headers = [];
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
	 * @param array|null $data Request arguments. If not supplied, $_GET & $_POST will be used
	 */
	function __construct(array $data=null) {
		// @FIXME avoid memory duplication
		$this->data = $data ?: array_merge($_GET,$_POST);
		$this->rawData = file_get_contents('php://input');
		$this->URI = strstr($_SERVER['REQUEST_URI'].'?','?',true);
		$this->QUERY = $_SERVER['QUERY_STRING'];
		foreach($_SERVER as $key=>$value) {
			if(substr($key,0,5)!='HTTP_') continue;
			$key = strtolower(str_replace('_','-',substr($key,5)));
			$this->headers[$key]=$value;
		}
		if(isset($this->headers['content-type']) && substr($this->headers['content-type'],0,16)=='application/json') {
			$this->data = array_merge($this->data, (array) json_decode($this->rawData));
		}
	}

	/**
	 * Return Request param
	 * @param string $p parameter name
	 * @return mixed|null
	 */
	function get($p) {
		return isset($this->data[$p]) ? $this->data[$p] : null;
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
		$this->data[$p]=$v;
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
	 * Returns the name of the HTTP method with which this request was made, for example, GET, POST, or PUT.
	 * @return string
	 */
	function getMethod() {
		return $_SERVER['REQUEST_METHOD'];
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
		return ($_SERVER['REQUEST_METHOD'] == 'PUT') ? $this->rawData : null;
	}

	function getRawData() {
		return $this->rawData;
	}

	/**
	 * @return boolean TRUE if Request method = GET
	 */
	function isGet() {
		return ($_SERVER['REQUEST_METHOD']=='GET');
	}

	/**
	 * @return boolean TRUE if Request method = GET
	 */
	function isPost() {
		return ($_SERVER['REQUEST_METHOD']=='POST');
	}
}