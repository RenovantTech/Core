<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\http\session;
use const metadigit\core\trace\T_INFO;
use metadigit\core\sys,
	metadigit\core\http\SessionException;
/**
 * HTTP Session Manager.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Manager {
	use \metadigit\core\CoreTrait;
	const ACL_SKIP = true;

	const EVENT_START	= 'http.session:start';
	const EVENT_END		= 'http.session:end';

	/** Cookie config
	 * @var array */
	protected $cookie = [
		'name'		=> 'SESSION',
		'lifetime'	=> 3600,
		'path'		=> '/',
		'domain'	=> null,
		'secure'	=> null,
		'httponly'	=> null
	];
	/** Handler config
	 * @var array */
	protected $handler = [
		'class' => 'metadigit\core\http\session\handler\Sqlite',
		'constructor' => [
			'pdo' => 'master',
			'table' => 'sys_auth_session'
		]
	];
	/** Session Handler
	 * @var \SessionHandlerInterface */
	protected $Handler;

	/**
	 * @throws SessionException
	 */
	function start() {
		if(PHP_SAPI=='cli') return;
		if(session_status() == PHP_SESSION_ACTIVE) throw new SessionException(11);
		if(headers_sent($file,$line)) throw new SessionException(12, [$file,$line]);
		session_name($this->cookie['name']);
		session_set_cookie_params($this->cookie['lifetime'], $this->cookie['path'], $this->cookie['domain'], $this->cookie['secure'], $this->cookie['httponly']);
		$this->Handler = new $this->handler['class'](...array_values((array)$this->handler['constructor']));
		session_set_save_handler($this->Handler, true);
		session_start();
		sys::event(self::EVENT_START);
	}

	/**
	 * Destroys all of the data associated with the current session.
	 */
	function destroy() {
		sys::trace(LOG_DEBUG, T_INFO, null, null, $this->_.'->destroy');
		session_destroy();
		if (isset($_COOKIE[$this->cookie['name']])) setcookie($this->cookie['name'], false, 315554400 /* 1980-01-01 */, $this->cookie['path'], $this->cookie['domain'], $this->cookie['secure'], $this->cookie['httponly']);
	}

	/**
	 * Shutdown the session, close writing and detach $_SESSION from the back-end storage mechanism.
	 * This will complete the internal data transformation on this request.
	 * @return void
	 */
	function end() {
		sys::event(self::EVENT_END);
		session_write_close();
	}
}
