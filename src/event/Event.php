<?php
namespace renovant\core\event;
class Event {

	/** Event's parameters
	 * @var array */
	protected $params = [];
	/** Event propagation flag
	 * @var bool */
	protected $_stopped = false;

	/**
	 * @param array $params Event's parameters
	 */
	function __construct(array $params=null) {
		$this->params = $params;
	}

	function __get($id) {
		return (isset($this->params[$id])) ? $this->params[$id]: null;
	}

	/**
	 * Verify is Event propagation was stopped
	 * @return boolean
	 */
	function isPropagationStopped() {
		return $this->_stopped;
	}

	/**
	 * Stop Event propagation
	 */
	function stopPropagation() {
		$this->_stopped = true;
	}
}
