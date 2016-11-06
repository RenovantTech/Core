<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\session;
use const metadigit\core\trace\T_INFO;
use function metadigit\core\trace;
/**
 * HTTP Session Manager.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class SessionManager {
	use \metadigit\core\CoreTrait;
	const ACL_SKIP = true;

	const EVENT_START = 'session.start';
	const EVENT_END = 'session.end';

	/** SessionHandler class
	 * @var string */
	protected $handlerClass = 'metadigit\core\session\handler\Sqlite';
	/** SessionHandler constructor params
	 * @var array */
	protected $handlerConfig = ['system', 'sessions'];
	/** The session name references the name of the session, which is used in cookies and URLs (e.g. PHPSESSID)
	 * @var string */
	protected $name = 'SESSION';
	/** Lifetime of the session cookie, defined in seconds.
	 * @var integer */
	protected $lifetime = 0;
	/** Path on the domain where the cookie will work. Use a single slash ('/') for all paths on the domain.
	 * @var string */
	protected $path = '/';
	/** Cookie domain, for example 'www.php.net'. To make cookies visible on all sub-domains then the domain must be prefixed with a dot like '.php.net'.
	 * @var string */
	protected $domain = '';
	/** If TRUE cookie will only be sent over secure connections.
	 * @var boolean */
	protected $secure = false;
	/** Session Handler
	 * @var object */
	protected $Handler;

	function start() {
		if(PHP_SAPI=='cli') return;
		if(session_status() == PHP_SESSION_ACTIVE) throw new SessionException(11);
		if(headers_sent($file,$line)) throw new SessionException(12, [$file,$line]);
		session_name($this->name);
		session_set_cookie_params($this->lifetime, $this->path, $this->domain, $this->secure);
		$this->Handler = (new \ReflectionClass($this->handlerClass))->newInstanceArgs($this->handlerConfig);
		session_set_save_handler($this->Handler, true);
		session_start();
		$this->context()->trigger(self::EVENT_START, $this);
	}

	/**
	 * Destroys all of the data associated with the current session.
	 */
	function destroy() {
		trace(LOG_DEBUG, T_INFO);
		session_destroy();
		if (isset($_COOKIE[$this->name])) setcookie($this->name, false, 315554400 /* 1980-01-01 */, $this->path, $this->domain, $this->secure);
	}

	/**
	 * Shutdown the session, close writing and detach $_SESSION from the back-end storage mechanism.
	 * This will complete the internal data transformation on this request.
	 * @return void
	 */
	function end() {
		$this->context()->trigger(self::EVENT_END, $this);
		session_write_close();
	}
}
