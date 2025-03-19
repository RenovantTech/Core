<?php
namespace renovant\core\http;

class Request {
	/** Request named attributes.
	 * @var	array */
	protected array $attrs = [];
	/** Request parameters (contents of $_GET, $_POST & $_COOKIE) */
	protected array $params = [];
	/** Request HTTP headers */
	protected array $headers = [];
	/** HTTP method */
	protected ?string $method;
	/** HTTP Request query */
	protected ?string $QUERY;
	/** POST/PUT raw data */
	protected string $rawData;
	/** HTTP Request URI */
	protected string $URI;

	/**
	 * Constructor: create a new HTTP Request
	 * @param string|null $uri the HTTP URI
	 * @param string|null $method the HTTP method
	 * @param array|null $params the GET/POST parameters
	 * @param array|null $headers
	 * @param string|null $data the raw body data
	 */
	public function __construct(string $uri = null, string $method = null, array $params = null, array $headers = null, string $data = null) {
		$this->URI    = strstr(($uri ?: $_SERVER['REQUEST_URI']) . '?', '?', true);
		$this->method = $method ?: $_SERVER['REQUEST_METHOD'];
		// @FIXME avoid memory duplication
		$this->params  = $params ?: array_merge($_GET, $_POST);
		$this->rawData = $data ?: file_get_contents('php://input');
		$this->QUERY   = $_SERVER['QUERY_STRING'];
		if ($headers) {
			foreach ($headers as $key => $value) {
				if (substr($key, 0, 5) == 'HTTP_') {
					$key = substr($key, 5);
				}
				$this->headers[strtolower(str_replace('_', '-', $key))] = $value;
			}
		} else {
			foreach ($_SERVER as $key => $value) {
				if (substr($key, 0, 5) != 'HTTP_') {
					continue;
				}
				$key                 = strtolower(str_replace('_', '-', substr($key, 5)));
				$this->headers[$key] = $value;
			}
		}
		if (isset($this->headers['content-type']) && substr($this->headers['content-type'], 0, 16) == 'application/json') {
			$this->params = array_merge($this->params, (array) json_decode($this->rawData, true));
		}
	}

	/**
	 * Return Request param
	 * @param string $p parameter name
	 * @return mixed|null
	 */
	public function get(string $p) {
		return $this->params[$p] ?? null;
	}

	public function getAttribute($k) {
		return $this->attrs[$k] ?? null;
	}

	/**
	 * Set Request param
	 * @param string $p parameter name
	 * @param mixed $v parameter value
	 */
	public function set(string $p, $v) {
		$this->params[$p] = $v;
	}

	public function setAttribute($k, $v) {
		$this->attrs[$k] = $v;
	}

	/**
	 * Return QUERY_STRING
	 */
	public function QUERY(): string|null {
		return $this->QUERY;
	}

	public function URI(): string {
		return $this->URI;
	}

	/**
	 * @return array GET data
	 */
	public function getGetData(): array {
		return $_GET;
	}

	public function getHeader($key) {
		$key = strtolower($key);
		return $this->headers[$key] ?? null;
	}

	/**
	 * Get the HTTP method (GET, POST, PUT, ...)
	 */
	public function getMethod(): string|null {
		return $this->method;
	}

	public function getJsonData(): array {
		return json_decode($this->rawData, true);
	}

	/**
	 * @return array POST data
	 */
	public function getPostData(): array {
		return $_POST;
	}

	/**
	 * @return false|string|null POST data
	 */
	public function getPutData() {
		return ($this->method == 'PUT') ? $this->rawData : null;
	}

	public function getRawData() {
		return $this->rawData;
	}

	/**
	 * @return boolean TRUE if Request method = GET
	 */
	public function isGet(): bool {
		return ($this->method == 'GET');
	}

	/**
	 * @return boolean TRUE if Request method = GET
	 */
	public function isPost(): bool {
		return ($this->method == 'POST');
	}
}
