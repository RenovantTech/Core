<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\console;
use const metadigit\core\trace\T_INFO;
use metadigit\core\sys;
/**
 * CLI Response.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Response {

	/** Response data, aka Models passed to the MVC View
	 * @var array */
	private $data = [];
	/** Exit status
	 * @var int */
	protected $exit = 0;
	/** Response size (bytes)
	 * @var int */
	private $size = 0;
	/** Current View/viewName
	 * @var ViewInterface|string|null */
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
	 * Returns the actual buffer size used for this Response. If no buffering is used, this method returns 0.
	 * @return int
	 */
	function getSize() {
		return ($this->size) ? $this->size : ob_get_length();
	}

	/**
	 * Get current View / viewName
	 * @return ViewInterface|null|string
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

	/** Set exit status
	 * @param int $exit
	 */
	function setExitStatus($exit) {
		$this->exit = $exit;
	}

	/**
	 * Set the View / viewName to be rendered with Response data
	 * @param ViewInterface|string $view
	 */
	function setView($view) {
		$this->View = $view;
	}

	// === methods ================================================================================

	/**
	 * Forces any content in the buffer to be written to the client.
	 * A call to this method automatically commits the Response.
	 */
	function send() {
		$this->size = ob_get_length();
		sys::trace(LOG_DEBUG, T_INFO, null, null. __METHOD__);
		ob_flush();
		ini_set('precision', 16);
		define('metadigit\core\trace\TRACE_END_TIME', microtime(1));
		ini_restore('precision');
	}

	/**
	 * Clears any data that exists in the buffer as well as the exit status.
	 */
	function reset() {
		$this->size = 0;
		$this->exit = 0;
		ob_clean();
	}
}
