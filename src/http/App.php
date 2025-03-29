<?php
namespace renovant\core\http;

use renovant\core\{sys,sysinfo,SysException};
use renovant\core\auth\{Auth, AuthException};
use renovant\core\event\{EventDispatcher, EventDispatcherException};
use renovant\core\context\{Context, ContextException};

use const renovant\core\trace\T_INFO;

class App {
	use \renovant\core\CoreTrait;

	protected string $name   = 'APP';
	protected array $modules = [];

	/**
	 * @param array $routes HTTP APP modules routing
	 * @throws ContextException
	 * @throws EventDispatcherException
	 * @throws \ReflectionException
	 */
	public function run(Request $Req, Response $Res) {
		$Event   = new Event($Req, $Res);
		$context = null;
		try {
			foreach ($this->modules as $module => $conf) {
				if (strpos($_SERVER['REQUEST_URI'], $conf['url']) === 0) {
					$context = $conf['context'] ?? strrchr($this->_, '.', true);
					break;
				}
			}
			if ($context === null) {
				throw new SysException(1, [strtoupper(PHP_SAPI), $_SERVER['SERVER_ADDR'], $_SERVER['SERVER_PORT'], $Req->URI()]);
			}
			sys::trace(LOG_DEBUG, T_INFO, 'matched URL: ' . $conf['url'] . ' => MODULE context: ' . $context);
			$Req->setAttribute('APP', $this->name);
			$Req->setAttribute('APP_MOD', $module);
			$Req->setAttribute('APP_MOD_CONTEXT', $context);
			$Req->setAttribute('APP_MOD_URI', '/' . ltrim('/' . substr($Req->URI(), strlen($conf['url'])), '/'));
			$Req->setAttribute('APP_MOD_DIR', sysinfo::dir($context . '.class') . '/');
			sys::event()->trigger(Event::EVENT_INIT, $Event);
			sys::context()->get($context . '.Dispatcher')->dispatch($Req, $Res);
		} catch (AuthException $Ex) {
			http_response_code(401);
			$Event->setException($Ex);
			sys::event()->trigger(Event::EVENT_EXCEPTION, $Event);
		} catch (SysException $Ex) {
			http_response_code(404);
			$Event->setException($Ex);
			sys::event()->trigger(Event::EVENT_EXCEPTION, $Event);
		} catch (\Exception $Ex) {
			http_response_code(500);
			$Event->setException($Ex);
			sys::event()->trigger(Event::EVENT_EXCEPTION, $Event);
		}
	}
}
