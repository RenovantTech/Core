<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\console;
/**
 * MVC Dispatch Event
 * Main event passed throughout MVC flow.
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
class Event extends \renovant\core\event\Event {

	const EVENT_INIT		= 'console:init';
	const EVENT_ROUTE		= 'console:route';
	const EVENT_CONTROLLER	= 'console:controller';
	const EVENT_VIEW		= 'console:view';
	const EVENT_RESPONSE	= 'console:response';
	const EVENT_EXCEPTION	= 'console:exception';
	const EVENT_SIGTERM		= 'console:sigterm';

	/** CLI Request
	 * @var Request */
	protected $Request;
	/** CLI Response
	 * @var Response */
	protected $Response;
	/** Controller, if any
	 * @var ControllerInterface */
	protected $Controller;
	/** View, if any
	 * @var ViewInterface */
	protected $View;
	/** Exception, if any
	 * @var \Exception */
	protected $Exception;

	function __construct(Request $Request, Response $Response) {
		$this->Request = $Request;
		$this->Response = $Response;
	}

	/**
	 * Get current CLI Request
	 * @return Request
	 */
	function getRequest() {
		return $this->Request;
	}

	/**
	 * Get current CLI Response
	 * @return Response
	 */
	function getResponse() {
		return $this->Response;
	}

	/**
	 * Get current Controller, if any
	 * @return ControllerInterface|null
	 */
	function getController() {
		return $this->Controller;
	}

	/**
	 * Get current View, if any
	 * @return ViewInterface|null
	 */
	function getView() {
		return $this->View;
	}

	/**
	 * Get current Exception, if any
	 * @return \Exception|null
	 */
	function getException() {
		return $this->Exception;
	}

	/**
	 * @param $Controller
	 */
	function setController($Controller) {
		$this->Controller = $Controller;
	}

	/**
	 * @param ViewInterface $View
	 */
	function setView(ViewInterface $View) {
		$this->View = $View;
	}

	/**
	 * @param \Exception $Exception
	 */
	function setException(\Exception $Exception) {
		$this->Exception = $Exception;
	}
}
