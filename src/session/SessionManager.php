<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\session;
use function metadigit\core\trace;
use metadigit\core\context\Context;
/**
 * HTTP Session Manager.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class SessionManager {
	use \metadigit\core\CoreTrait;

	const EVENT_START = 'session.start';
	const EVENT_END = 'session.end';
	/** The session name references the name of the session, which is used in cookies and URLs (e.g. PHPSESSID)
	 * @var string */
	protected $name = 'SESSION';
	/** Lifetime of the session cookie, defined in seconds.
	 * @var integer */
	protected $lifetime = 0;
	/** Path on the domain where the cookie will work. Use a single slash ('/') for all paths on the domain.
	 * @var string */
	protected $path = '/';
	/** Cookie domain, for example 'www.php.net'. To make cookies visible on all subdomains then the domain must be prefixed with a dot like '.php.net'.
	 * @var string */
	protected $domain = '';
	/** If TRUE cookie will only be sent over secure connections.
	 * @var boolean */
	protected $secure = false;
	/** Session Handler
	 * @var object */
	protected $Handler;

	/**
	 * @param string $handlerClass handler class
	 * @param array $handlerConfig handler config
	 */
	function __construct($handlerClass='metadigit\core\session\handler\Sqlite', array $handlerConfig=['system', 'sessions']) {
		$this->Handler = (new \ReflectionClass($handlerClass))->newInstanceArgs($handlerConfig);
	}

	function start() {
		if(PHP_SAPI=='cli') return;
		if(session_status() == 2) throw new SessionException(11);
		if(headers_sent($file,$line)) throw new SessionException(12, [$file,$line]);
		session_name($this->name);
		session_set_cookie_params($this->lifetime, $this->path, $this->domain, $this->secure);
		session_set_save_handler($this->Handler, true);
		session_start();
		$this->context()->trigger(self::EVENT_START, $this);
	}

	/**
	 * Destroys all of the data associated with the current session.
	 */
	function destroy() {
		TRACE and trace(LOG_DEBUG, TRACE_DEFAULT);
		session_destroy();
		if (isset($_COOKIE[$this->name])) setcookie($this->name, false, 315554400 /* strtotime('1980-01-01')*/, $this->path, $this->domain, $this->secure);
	}

	/**
	 * Shutdown the session, close writing and detach $_SESSION from the back-end storage mechanism.
	 * This will complete the internal data transformation on this request.
	 * @param bool $readonly - OPTIONAL remove write access (i.e. throw error if Zend_Session's attempt writes)
	 * @return void
	 */
	function end($readonly=true) {
		$this->context()->trigger(self::EVENT_END, $this);
		session_write_close();
	}
}
