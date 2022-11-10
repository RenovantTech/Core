<?php
namespace renovant\core\http;
use const renovant\core\trace\T_INFO;
use renovant\core\sys;
class Response {

	const DEFAULT_CONTENT_TYPE = 'text/html';

	/** Response data, aka Models passed to the MVC View */
	private array $data = [];
	/** HTTP header "Content-Type" */
	private string $contentType = self::DEFAULT_CONTENT_TYPE;
	/** Response size (bytes) */
	private int $size = 0;
	/** Current View name */
	private ?string $view = null;
	/** Current View Engine */
	private ViewInterface|string|false|null $viewEngine = null;
	/** Current View options */
	private ?array $viewOptions = null;

	// === getter & setter ========================================================================

	/**
	 * Get Response data by key
	 * @param string $key
	 * @return mixed|null
	 */
	function get(string $key) {
		return (isset($this->data[$key])) ? $this->data[$key] : null;
	}

	/**
	 * Get all Response data (array)
	 */
	function getData(): array {
		return $this->data;
	}

	/**
	 * Get HTTP Status Code
	 */
	function getCode(): int {
		return http_response_code();
	}

	/**
	 * Get current Response output
	 */
	function getContent(): string {
		return ob_get_contents();
	}

	/**
	 * Get HTTP header "Content-Type"
	 */
	function getContentType(): string {
		return $this->contentType;
	}

	/**
	 * Returns the actual buffer size used for this Response. If no buffering is used, this method returns 0.
	 */
	function getSize(): int {
		return ($this->size) ?: ob_get_length();
	}

	/**
	 * Get View, options and engine
	 * @return array ViewInterface|null|string
	 */
	function getView(): array {
		return [$this->view, $this->viewOptions, $this->viewEngine];
	}

	// === methods ================================================================================

	/**
	 * Set HTTP Status Code
	 * @param int $code HTTP Status Code
	 * @return Response (fluent interface)
	 */
	function code(int $code): Response {
		http_response_code($code);
		return $this;
	}

	/**
	 * Set Response output, erasing existing content
	 * @param string $output
	 * @return Response (fluent interface)
	 */
	function content(string $output): Response {
		ob_clean();
		echo $output;
		return $this;
	}

	/**
	 * Set HTTP header "Content-Type"
	 */
	function contentType(string $contentType): Response {
		$this->contentType = $contentType;
		return $this;
	}

	/**
	 * Set HTTP Cookie
	 * wrapper for native setcookie() function, @see http://php.net/manual/en/function.setcookie.php
	 */
	function cookie(string $name, string $value='', int $expire=0, string $path='', string $domain='', bool $secure=true, bool $httpOnly=false, string $sameSite='Lax'): Response {
		setcookie($name, $value, [
			'expires'	=> $expire,
			'path'		=> $path,
			'domain'	=> $domain,
			'secure'	=> $secure,
			'httponly'	=> $httpOnly,
			'samesite'	=> $sameSite
		]);
		return $this;
	}

	/**
	 * Set HTTP header
	 * @param string $value
	 * @return Response (fluent interface)
	 */
	function header(string $value): Response {
		header($value);
		return $this;
	}

	/**
	 * Sends a temporary redirect response to the client using the specified redirect location URL.
	 * After using this method, the response should be considered to be committed and should not be written to.
	 * @param string $location URL to be redirect to
	 * @param int $statusCode the HTTP status code, defaults to 302.
	 */
	function redirect(string $location, int $statusCode=302) {
		ob_clean();
		$this->view = null;
		$this->viewEngine = null;
		// @TODO check that Dispatcher stop normal flow and exit .. maybe use an Exception ... MVCRedirectException ...
		if(substr($location,0,4)!='http'){
			$url = (isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']==true) ? 'https://' : 'http://';
			$url .= $_SERVER['SERVER_NAME'];
			if($_SERVER['SERVER_PORT']!=80) $url .= ':'.$_SERVER['SERVER_PORT'];
			if(substr($location,0,1)!='/') $url .= dirname($_SERVER['REQUEST_URI']).'/';
			$location = $url.$location;
		}
		sys::trace(LOG_DEBUG, T_INFO, 'REDIRECT to '.$location, null, 'sys.http.Response->redirect');
		header('Location: '.$location, true, $statusCode);
		if(session_status() == PHP_SESSION_ACTIVE) session_write_close();
	}

	/**
	 * Clears any data that exists in the buffer as well as the status code and headers.
	 * @return Response (fluent interface)
	 */
	function reset(): Response {
		$this->size = 0;
		ob_clean();
		return $this;
	}

	/**
	 * Forces any content in the buffer to be written to the client.
	 * A call to this method automatically commits the Response, meaning the status code and headers will be written.
	 * @return void
	 */
	function send() {
		$this->size = ob_get_length();
		if($this->size) header('Content-Type: '.(($this->contentType)?:self::DEFAULT_CONTENT_TYPE));
		sys::trace(LOG_DEBUG, T_INFO, null, null, 'sys.http.Response->send');
		ob_flush();
		function_exists('fastcgi_finish_request') and fastcgi_finish_request();
		ini_set('precision', 16);
		define('renovant\core\trace\TRACE_END_TIME', microtime(1));
		ini_restore('precision');
	}

	/**
	 * Store Response data
	 * @param string|array $k data key or array
	 * @param mixed|null $v data value
	 * @return Response (fluent interface)
	 */
	function set($k, $v=null): Response {
		if(is_array($k)) $this->data = array_merge($this->data, $k);
		elseif(is_string($k) && preg_match('/^[a-zA-Z]+/',$k)) $this->data[$k] = $v;
		else trigger_error(__METHOD__.': invalid key');
		return $this;
	}

	/**
	 * Set the View name to be rendered with Response data
	 * @param string|null $view View name
	 * @param array|null $options View options
	 * @param \renovant\core\http\ViewInterface|string|integer|null $engine View Engine to be used
	 * @return Response
	 */
	function setView(?string $view, ?array $options=null, ViewInterface|string|bool|null $engine=null): Response {
		if($view) $this->view = $view;
		if($options) $this->viewOptions = $options;
		if(!is_null($engine)) $this->viewEngine = $engine;
		return $this;
	}
}
