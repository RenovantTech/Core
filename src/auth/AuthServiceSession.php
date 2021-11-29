<?php
namespace renovant\core\auth;
use const renovant\core\trace\T_INFO;
use renovant\core\sys,
	renovant\core\http\Event as HttpEvent;
class AuthServiceSession extends AuthService {

	function __sleep() {
		return ['_', 'cookieAUTH', 'cookieREFRESH', 'cookieREMEMBER', 'cookieXSRF', 'ttlAUTH', 'ttlREFRESH', 'ttlREMEMBER', 'skipAuthModules', 'skipAuthUrls', 'skipXSRFModules', 'skipXSRFUrls'];
	}

	/**
	 * Initialize AUTH module, perform Authentication & Security checks
	 * To be invoked via event listener before HTTP Controller execution (HTTP:INIT, HTTP:ROUTE or HTTP:CONTROLLER).
	 * @param HttpEvent $Event
	 * @throws AuthException
	 * @throws Exception
	 * @throws \Exception
	 */
	function init(HttpEvent $Event) {
		$prevTraceFn = sys::traceFn($this->_.'->init');
		try {
			if (!isset($_SESSION)) throw new Exception(23);
			if (isset($_SESSION['__AUTH__']) && is_array($_SESSION['__AUTH__'])) {
				$this->doAuthenticate($_SESSION['__AUTH__']);
				sys::trace(LOG_DEBUG, T_INFO, 'SESSION AUTH OK', $_SESSION['__AUTH__']);
			}

			parent::doInit($Event);
		} catch (AuthException $Ex) {
			$this->_commit = true;
			throw $Ex;
		} finally {
			$this->commit(); // need on Exception to regenerate JWT/SESSION & XSRF-TOKEN
			sys::traceFn($prevTraceFn);
		}
	}

	/**
	 * Commit AUTH data & XSRF-TOKEN to module storage.
	 * To be invoked via event listener after HTTP Controller execution (HTTP:VIEW & HTTP:EXCEPTION).
	 * @throws \Exception
	 */
	function commit() {
		if(!$this->_commit) return;
		$prevTraceFn = sys::traceFn($this->_.'->commit');

		$Auth = Auth::instance();
		if(!$Auth->UID()) return;
		try {
			// AUTH DATA
			$data = array_merge($Auth->data(), [
				'GID' => $Auth->GID(),
				'GROUP' => $Auth->GROUP(),
				'NAME' => $Auth->NAME(),
				'UID' => $Auth->UID()
			]);
			sys::trace(LOG_DEBUG, T_INFO, 'update SESSION data');
			$_SESSION['__AUTH__'] = $data;

			// REMEMBER cookie
			if($this->rememberFlag) {
				// @TODO
			}

			parent::doCommit();
		} finally {
			$this->_commit = false; // avoid double invocation on init() Exception
			sys::traceFn($prevTraceFn);
		}
	}

	/**
	 * Erase AUTH data.
	 * To be invoked on LOGOUT or other required situations.
	 * @throws \Exception
	 */
	function erase() {
		$prevTraceFn = sys::traceFn($this->_.'->erase');
		try {
			// delete AUTH DATA
			sys::trace(LOG_DEBUG, T_INFO, 'erase SESSION data');
			session_regenerate_id(false);
			unset($_SESSION['__AUTH__']);

			// delete REMEMBER cookie
			// @TODO

			parent::doErase();
		} finally {
			$this->_commit = false;
			sys::traceFn($prevTraceFn);
		}
	}
}
