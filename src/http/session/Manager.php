<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace metadigit\core\http\session;
use const metadigit\core\trace\T_INFO;
use metadigit\core\sys,
	metadigit\core\container\Container,
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
		'secure'	=> true,
		'httponly'	=> true
	];
	/** Handler config
	 * @var array */
	protected $handlerCnf = [
		'class' => 'metadigit\core\http\session\handler\Mysql',
		'constructor' => null,
		'properties' => [
			'pdo' => 'master',
			'table' => 'sys_auth_session'
		]
	];
	/** Session Handler
	 * @var \SessionHandlerInterface */
	protected $Handler;

	/**
	 * Manager constructor.
	 * @param array $cookie cookie params
	 * @param array $handler Handler config
	 */
	function __construct(array $cookie=null, array $handler=null) {
		if($cookie) $this->cookie = $cookie;
		if($handler) $this->handlerCnf = array_merge(Container::YAML_OBJ_SKELETON, $handler);
		$this->Handler = (new Container())->build('sys.http.SessionHandler', $this->handlerCnf['class'], $this->handlerCnf['constructor'], $this->handlerCnf['properties']);
		$this->Handler->init();
	}

	/**
	 * @throws SessionException
	 */
	function start() {
		if(PHP_SAPI=='cli') return;
		if(session_status() == PHP_SESSION_ACTIVE) throw new SessionException(11);
		if(headers_sent($file,$line)) throw new SessionException(12, [$file,$line]);
		session_name($this->cookie['name']);
		session_set_cookie_params($this->cookie['lifetime'], $this->cookie['path'], $this->cookie['domain'], $this->cookie['secure'], $this->cookie['httponly']);
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
