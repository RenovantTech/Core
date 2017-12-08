<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\event;
/**
 * Base Event class
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Event {

	/** Event name
	 * @var string */
	protected $name;
	/** Event target
	 * @var mixed */
	protected $target = null;
	/** Event's parameters
	 * @var array */
	protected $params = [];
	/** Event propagation flag
	 * @var bool */
	protected $_stopped = false;

	/**
	 * @param mixed $target Event's target
	 * @param array $params Event's parameters
	 */
	function __construct($target=null, array $params=null) {
		$this->target = $target;
		$this->params = $params;
	}

	function __get($id) {
		return (isset($this->params[$id])) ? $this->params[$id]: null;
	}

	/**
	 * Return the Event's name
	 * @return string
	 */
	function getName() {
		return $this->name;
	}

	/**
	 * Return the Event's context
	 * @return mixed
	 */
	function getTarget() {
		return $this->target;
	}

	/**
	 * @param string $name Event name
	 */
	function setName($name) {
		$this->name = $name;
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
