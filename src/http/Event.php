<?php
namespace renovant\core\http;
class Event extends \renovant\core\event\Event {

	const EVENT_INIT		= 'http:init';
	const EVENT_ROUTE		= 'http:route';
	const EVENT_CONTROLLER	= 'http:controller';
	const EVENT_VIEW		= 'http:view';
	const EVENT_RESPONSE	= 'http:response';
	const EVENT_EXCEPTION	= 'http:exception';

	/** HTTP Request
	 * @var \renovant\core\http\Request */
	protected $Request;
	/** HTTP Response
	 * @var \renovant\core\http\Response */
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
	 * Get current HTTP Request
	 * @return \renovant\core\http\Request
	 */
	function getRequest() {
		return $this->Request;
	}

	/**
	 * Get current HTTP Response
	 * @return \renovant\core\http\Response
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
