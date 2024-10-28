<?php
namespace renovant\core\console;

class Event extends \renovant\core\event\Event {
	public const EVENT_INIT       = 'console:init';
	public const EVENT_ROUTE      = 'console:route';
	public const EVENT_CONTROLLER = 'console:controller';
	public const EVENT_VIEW       = 'console:view';
	public const EVENT_RESPONSE   = 'console:response';
	public const EVENT_EXCEPTION  = 'console:exception';
	public const EVENT_SIGTERM    = 'console:sigterm';

	/** CLI Request */
	protected Request $Request;
	/** CLI Response */
	protected Response $Response;
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
	 * Get current CLI Request
	 */
	public function getRequest(): Request {
		return $this->Request;
	}

	/**
	 * Get current CLI Response
	 */
	public function getResponse(): Response {
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
