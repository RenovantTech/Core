<?php
namespace renovant\core\http;

class Event extends \renovant\core\event\Event {
	public const EVENT_INIT       = 'http:init';
	public const EVENT_ROUTE      = 'http:route';
	public const EVENT_CONTROLLER = 'http:controller';
	public const EVENT_VIEW       = 'http:view';
	public const EVENT_RESPONSE   = 'http:response';
	public const EVENT_EXCEPTION  = 'http:exception';

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

	public function __construct(Request $Request, Response $Response) {
		$this->Request  = $Request;
		$this->Response = $Response;
	}

	/**
	 * Get current HTTP Request
	 * @return \renovant\core\http\Request
	 */
	public function getRequest() {
		return $this->Request;
	}

	/**
	 * Get current HTTP Response
	 * @return \renovant\core\http\Response
	 */
	public function getResponse() {
		return $this->Response;
	}

	/**
	 * Get current Controller, if any
	 * @return ControllerInterface|null
	 */
	public function getController() {
		return $this->Controller;
	}

	/**
	 * Get current View, if any
	 * @return ViewInterface|null
	 */
	public function getView() {
		return $this->View;
	}

	/**
	 * Get current Exception, if any
	 * @return \Exception|null
	 */
	public function getException() {
		return $this->Exception;
	}

	/**
	 * @param $Controller
	 */
	public function setController($Controller) {
		$this->Controller = $Controller;
	}

	/**
	 * @param ViewInterface $View
	 */
	public function setView(ViewInterface $View) {
		$this->View = $View;
	}

	/**
	 * @param \Exception $Exception
	 */
	public function setException(\Exception $Exception) {
		$this->Exception = $Exception;
	}
}
