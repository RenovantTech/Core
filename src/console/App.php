<?php
namespace renovant\core\console;

use renovant\core\{sys,SysException};
use renovant\core\event\{EventDispatcher, EventDispatcherException};
use renovant\core\context\{Context, ContextException};

use const renovant\core\trace\T_INFO;

class App {
	use \renovant\core\CoreTrait;

	protected string $name   = 'APP';
	protected array $modules = [];

	/**
	 * @param array $routes CLI APP modules routing
	 * @throws ContextException
	 * @throws EventDispatcherException
	 * @throws SysException
	 * @throws \ReflectionException
	 */
	public function run(Request $Req, Response $Res) {
		$Event     = new Event($Req, $Res);
		$namespace = null;
		try {
			foreach ($this->modules as $module => $conf) {
				if (strpos($Req->CMD(), $conf['cmd']) === 0) {
					$namespace = $conf['namespace'];
					break;
				}
			}
			if ($namespace === null) {
				throw new SysException(1, [PHP_SAPI, ...explode(' ', $Req->CMD())]);
			}
			$Req->setAttribute('APP', $this->name);
			$Req->setAttribute('APP_MOD', $module);
			$Req->setAttribute('APP_MOD_NAMESPACE', $namespace);
			$Req->setAttribute('APP_MOD_URI', trim(strstr($Req->CMD(), ' ')));
			$Req->setAttribute('APP_MOD_DIR', sys::info($namespace . '.class', sys::INFO_PATH_DIR) . '/');
			sys::event()->trigger(Event::EVENT_INIT, $Event);
			sys::context()->get($namespace . '.Dispatcher')->dispatch($Req, $Res);
		} catch (\Exception $Ex) {
			//@TODO set CLI exit() code
			//http_response_code($Ex->getCode());
			$Event->setException($Ex);
			sys::event()->trigger(Event::EVENT_EXCEPTION, $Event);
		}
	}
}
