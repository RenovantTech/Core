<?php
namespace renovant\core\auth;
use renovant\core\http\Request,
	renovant\core\http\Response;
class Event extends \renovant\core\event\Event {

	const EVENT_LOGIN		= 'auth:login';

	protected Auth $Auth;

	function __construct(Auth $Auth) {
		$this->Auth = $Auth;
	}

	/**
	 * Get current Auth
	 */
	function getAuth(): Auth {
		return $this->Auth;
	}
}
