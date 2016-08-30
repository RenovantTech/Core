<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\http;
use function metadigit\core\trace;
use metadigit\core\Kernel;
/**
 * HTTP Response.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Response {

	const DEFAULT_MIME = 'text/html';

	/** Response data, aka Models passed to the MVC View
	 * @var array */
	private $data = [];
	/** HTTP header "Content-Type"
	 * @var string */
	private $contentType;
	/** Response size (bytes)
	 * @var int */
	private $size = 0;
	/** Current View/viewName
	 * @var \metadigit\core\web\ViewInterface|string|null */
	private $View = null;

	function __construct() {
		ob_start();
	}

	function __destruct() {
		ob_end_clean();
	}

	// === getter & setter ========================================================================

	/**
	 * Get Response data by key
	 * @param string $key
	 * @return mixed|null
	 */
	function get($key) {
		return (isset($this->data[$key])) ? $this->data[$key] : null;
	}

	/**
	 * Get all Response data (array)
	 * @return array
	 */
	function getData() {
		return $this->data;
	}

	/**
	 * Get current Response output
	 * @return string
	 */
	function getContent() {
		return ob_get_contents();
	}

	/**
	 * Get HTTP header "Content-Type"
	 * @return string
	 */
	function getContentType() {
		return $this->contentType;
	}

	/**
	 * Returns the actual buffer size used for this Response. If no buffering is used, this method returns 0.
	 * @return int
	 */
	function getSize() {
		return ($this->size) ?: ob_get_length();
	}

	/**
	 * Get current View / viewName
	 * @return \metadigit\core\web\ViewInterface|null|string
	 */
	function getView() {
		return $this->View;
	}

	/**
	 * Store Response data
	 * @param string|array $k data key or array
	 * @param mixed|null $v data value
	 * @return Response (fluent interface)
	 */
	function set($k, $v=null) {
		if(is_array($k)) $this->data = array_merge($this->data, $k);
		elseif(is_string($k) && preg_match('/^[a-zA-Z]+/',$k)) $this->data[$k] = $v;
		else trigger_error(__METHOD__.': invalid key');
		return $this;
	}

	/**
	 * Set Response output, erasing existing content
	 * @param $output
	 * @return Response (fluent interface)
	 */
	function setContent($output) {
		ob_clean();
		echo $output;
		return $this;
	}

	/**
	 * Set HTTP header "Content-Type"
	 * @param string $contentType
	 * @return Response (fluent interface)
	 */
	function setContentType($contentType) {
		if(!$this->contentType) $this->contentType = $contentType;
		return $this;
	}

	/**
	 * Set the View / viewName to be rendered with Response data
	 * @param \metadigit\core\web\ViewInterface|string $view
	 */
	function setView($view) {
		$this->View = $view;
	}

	// === methods ================================================================================

	/**
	 * Forces any content in the buffer to be written to the client.
	 * A call to this method automatically commits the Response, meaning the status code and headers will be written.
	 * @return void
	 */
	function send() {
		$this->size = ob_get_length();
		if($this->size) header('Content-Type: '.(($this->contentType)?:self::DEFAULT_MIME));
		TRACE and trace(LOG_DEBUG, TRACE_DEFAULT, null, null, __METHOD__);
		ob_flush();
		function_exists('fastcgi_finish_request') and fastcgi_finish_request();
		define('TRACE_END_TIME', microtime(1));
	}

	/**
	 * Sends a temporary redirect response to the client using the specified redirect location URL.
	 * After using this method, the response should be considered to be committed and should not be written to.
	 * @param string $location URL to be redirect to
	 * @param int $statusCode the HTTP status code, defaults to 302.
	 */
	function redirect($location, $statusCode=302) {
		ob_clean();
		$this->View = null;
		// @TODO check that Dispatcher stop normal flow and exit .. maybe use an Exception ... MVCRedirectException ...
		if(substr($location,0,4)!='http'){
			$url = (isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']==true) ? 'https://' : 'http://';
			$url .= $_SERVER['SERVER_NAME'];
			if($_SERVER['SERVER_PORT']!=80) $url .= ':'.$_SERVER['SERVER_PORT'];
			if(substr($location,0,1)!='/') $url .= dirname($_SERVER['REQUEST_URI']).'/';
			$location = $url.$location;
		}
		trace(LOG_DEBUG, TRACE_DEFAULT, 'REDIRECT to '.$location, null, __METHOD__);
		header('Location: '.$location, true, $statusCode);
		if(session_status() == PHP_SESSION_ACTIVE) session_write_close();
	}

	/**
	 * Clears any data that exists in the buffer as well as the status code and headers.
	 */
	function reset() {
		$this->size = 0;
		ob_clean();
	}
}
