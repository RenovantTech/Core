<?php
namespace renovant\core\console;

use renovant\core\sys;

use const renovant\core\trace\T_INFO;

class Response {
	public const LINE_NORMAL  = 0;
	public const LINE_INFO    = 1;
	public const LINE_SUCCESS = 11;
	public const LINE_WARNING = 12;
	public const LINE_ERROR   = 13;

	/** Response data, aka Models passed to the MVC View */
	protected array $data = [];
	/** Exit status */
	protected int $exit = 0;
	/** Output buffer ON/OFF */
	protected bool $outputBuffer = false;
	/** Output stream
	 * @var resource */
	protected $STDOUT = STDOUT;
	/** Current View/viewName
	 * @var ViewInterface|string|null */
	protected $View = null;

	public function __destruct() {
		if ($this->outputBuffer) {
			ob_end_clean();
		}
	}

	// === getter & setter ========================================================================

	/**
	 * Get Response data by key
	 * @param string $key
	 * @return mixed|null
	 */
	public function get($key) {
		return $this->data[$key] ?? null;
	}

	/**
	 * Get all Response data (array)
	 */
	public function getData(): array {
		return $this->data;
	}

	/**
	 * Get current Response output
	 * @return string
	 */
	public function getContent() {
		return $this->outputBuffer ? ob_get_contents() : file_get_contents($this->STDOUT);
	}

	/**
	 * Returns the actual buffer size used for this Response. If no buffering is used, this method returns 0.
	 * @return int
	 */
	public function getSize() {
		return $this->outputBuffer ? ob_get_length() : 0;
	}

	/**
	 * Get current View / viewName
	 * @return ViewInterface|null|string
	 */
	public function getView() {
		return $this->View;
	}

	/**
	 * Store Response data
	 * @param string|array $k data key or array
	 * @param mixed|null $v data value
	 * @return Response (fluent interface)
	 */
	public function set($k, $v = null) {
		if (is_array($k)) {
			$this->data = array_merge($this->data, $k);
		} elseif (is_string($k) && preg_match('/^[a-zA-Z]+/', $k)) {
			$this->data[$k] = $v;
		} else {
			trigger_error(__METHOD__ . ': invalid key');
		}
		return $this;
	}

	/**
	 * Set Response output, erasing existing content
	 * @param $output
	 * @return Response (fluent interface)
	 */
	public function setContent($output) {
		if ($this->outputBuffer) {
			ob_clean();
		}
		fwrite($this->STDOUT, $output);
		return $this;
	}

	/**
	 * Set exit status
	 * @param int $exit
	 */
	public function setExitStatus($exit) {
		$this->exit = $exit;
	}

	/**
	 * Set Output stream
	 * @param resource $handle
	 * @throws Exception
	 */
	public function setOutput($handle) {
		if (!is_resource($handle)) {
			throw new Exception(31);
		}
		if (!is_writable(stream_get_meta_data($handle)['uri'])) {
			throw new Exception(31);
		}
		if (stream_get_meta_data($handle)['mode'] == 'r') {
			throw new Exception(31);
		}
		$this->STDOUT = $handle;
	}

	/**
	 * Set the View / viewName to be rendered with Response data
	 * @param ViewInterface|string $view
	 */
	public function setView($view) {
		$this->View = $view;
	}

	// === OUTPUT methods =========================================================================

	public function error($text, $newLine = false) {
		$this->write($text, false, self::LINE_ERROR);
	}

	public function errorLn($text) {
		$this->write($text, true, self::LINE_ERROR);
	}

	public function info($text, $newLine = false) {
		$this->write($text, false, self::LINE_INFO);
	}

	public function infoLn($text) {
		$this->write($text, true, self::LINE_INFO);
	}

	public function success($text, $newLine = false) {
		$this->write($text, false, self::LINE_SUCCESS);
	}

	public function successLn($text) {
		$this->write($text, true, self::LINE_SUCCESS);
	}

	public function warning($text, $newLine = false) {
		$this->write($text, false, self::LINE_WARNING);
	}

	public function warningLn($text) {
		$this->write($text, true, self::LINE_WARNING);
	}

	/**
	 * @param string $text output message
	 * @param boolean $newLine add new line at end
	 * @param $type
	 */
	public function write($text, $newLine = false, $type = self::LINE_NORMAL) {
		fwrite($this->STDOUT, $text . ($newLine ? chr(10) : ''));
	}

	public function writeLn($text) {
		$this->write($text, true);
	}

	// === methods ================================================================================

	/**
	 * Forces any content in the buffer to be written to the client.
	 * A call to this method automatically commits the Response.
	 */
	public function send() {
		sys::trace(LOG_DEBUG, T_INFO, null, null . 'sys.console.Response->send');
		if ($this->outputBuffer) {
			ob_flush();
		}
		ini_set('precision', 16);
		define('renovant\core\trace\TRACE_END_TIME', microtime(1));
		ini_restore('precision');
	}

	/**
	 * Clears any data that exists in the buffer as well as the exit status.
	 */
	public function reset() {
		$this->exit = 0;
		if ($this->outputBuffer) {
			ob_clean();
		}
	}
}
