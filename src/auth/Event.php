<?php
namespace renovant\core\auth;

use renovant\core\http\{Request, Response};

class Event extends \renovant\core\event\Event {
	public const EVENT_LOGIN = 'auth:login';

	protected Auth $Auth;

	public function __construct(Auth $Auth) {
		$this->Auth = $Auth;
	}

	/**
	 * Get current Auth
	 */
	public function getAuth(): Auth {
		return $this->Auth;
	}
}
