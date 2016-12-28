<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\http;
/**
 * MVC Dispatch Event
 * Main event passed throughout MVC flow.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class DispatcherEvent extends \metadigit\core\event\Event {

	const EVENT_ROUTE		= 'dispatcher:route';
	const EVENT_CONTROLLER	= 'dispatcher:controller';
	const EVENT_VIEW		= 'dispatcher:view';
	const EVENT_RESPONSE	= 'dispatcher:response';
	const EVENT_EXCEPTION	= 'dispatcher:exception';

	/** HTTP Request
	 * @var \metadigit\core\http\Request */
	protected $Request;
	/** HTTP Response
	 * @var \metadigit\core\http\Response */
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
	 * @return \metadigit\core\http\Request
	 */
	function getRequest() {
		return $this->Request;
	}

	/**
	 * Get current HTTP Response
	 * @return \metadigit\core\http\Response
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
