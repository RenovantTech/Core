<?php
namespace renovant\core\console;

use renovant\core\{sys,sysinfo,SysException};
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
		$Event   = new Event($Req, $Res);
		$context = null;
		try {
			foreach ($this->modules as $module => $conf) {
				if (strpos($Req->CMD(), $conf['cmd']) === 0) {
					$context = $conf['context'] ?? strrchr($this->_, '.', true);
					break;
				}
			}
			if ($context === null) {
				throw new SysException(1, [PHP_SAPI, ...explode(' ', $Req->CMD())]);
			}
			$Req->setAttribute('APP', $this->name);
			$Req->setAttribute('APP_MOD', $module);
			$Req->setAttribute('APP_MOD_CONTEXT', $context);
			$Req->setAttribute('APP_MOD_URI', trim(strstr($Req->CMD(), ' ')));
			$Req->setAttribute('APP_MOD_DIR', sysinfo::dir($context . '.class') . '/');
			sys::event()->trigger(Event::EVENT_INIT, $Event);
			sys::context()->get($context . '.Dispatcher')->dispatch($Req, $Res);
		} catch (\Exception $Ex) {
			//@TODO set CLI exit() code
			//http_response_code($Ex->getCode());
			$Event->setException($Ex);
			sys::event()->trigger(Event::EVENT_EXCEPTION, $Event);
		}
	}
}
