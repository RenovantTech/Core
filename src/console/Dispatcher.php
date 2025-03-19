<?php
namespace renovant\core\console;

use renovant\core\sys;
use renovant\core\console\view\PhpView;
use renovant\core\trace\Tracer;

use const renovant\core\ENVIRONMENT;
use const renovant\core\trace\T_INFO;

class Dispatcher {
	use \renovant\core\CoreTrait;
	public const SIGNALS = [
		SIGHUP  => 'SIGHUP',
		SIGINT  => 'SIGINT',
		SIGQUIT => 'SIGQUIT',
		SIGABRT => 'SIGABRT',
		SIGUSR1 => 'SIGUSR1',
		SIGUSR2 => 'SIGUSR2',
		SIGTERM => 'SIGTERM',
		SIGCHLD => 'SIGCHLD',
		SIGCONT => 'SIGCONT'
	];

	/** default View engine */
	protected string $defaultViewEngine = 'php';
	/** Array of routes between Request URLs and Controllers names */
	protected array $routes = [];
	/** customizable templates dir path, default to \renovant\core\PUBLIC_DIR */
	protected string $resourcesDir = \renovant\core\PUBLIC_DIR;
	/** View engines mapping */
	protected array $viewEngines = [
		'php' => PhpView::class
		//		'smarty'	=> view\SmartyView::class
		//		'twig'		=> view\TwigView::class
	];

	/**
	 * @throws \ReflectionException
	 * @throws \renovant\core\event\EventDispatcherException
	 * @throws \renovant\core\context\ContextException
	 */
	public function dispatch(Request $Req, Response $Res) {
		$Controller = $controllerID = $resource = null;
		$Event      = new Event($Req, $Res);
		try {
			sys::cmd()->onStart($Req, $Res);
			if (!sys::event()->trigger(Event::EVENT_ROUTE, $Event)->isPropagationStopped()) {
				$controllerID = $this->doRoute($Req, $Res);
				$Controller   = sys::context()->get($controllerID, ControllerInterface::class);
				$Event->setController($Controller);
			}
			if ($Controller) {
				if (ENVIRONMENT != 'PHPUNIT') {
					$signalFn = function ($sig) use ($Controller, $Event, $controllerID, $Req, $Res) {
						sys::trace(LOG_DEBUG, T_INFO, self::SIGNALS[$sig], null, 'sys');
						$method = 'on' . self::SIGNALS[$sig];
						if (method_exists(sys::context()->container()->getType($controllerID), $method)) {
							$Controller->$method();
						}
						if ($sig == SIGTERM) {
							sys::event()->trigger(Event::EVENT_SIGTERM, $Event);
							sys::cmd()->onSIGTERM($Req->CMD());
							exit;
						}
					};
					foreach (self::SIGNALS as $k => $v) {
						pcntl_signal($k, $signalFn);
					}
				}
				if (!sys::event()->trigger(Event::EVENT_CONTROLLER, $Event)->isPropagationStopped()) {
					$Controller->handle($Req, $Res);
				}
			}
			if ($View = $Res->getView() ?: $Event->getView()) {
				if (is_string($View)) {
					list($View, $resource) = $this->resolveView($View, $Req, $Res);
				}
				if (!$View instanceof ViewInterface) {
					throw new Exception(13);
				}
				$Event->setView($View);
				if (!sys::event()->trigger(Event::EVENT_VIEW, $Event)->isPropagationStopped()) {
					$View->render($Req, $Res, $resource);
				}
			}
			sys::event()->trigger(Event::EVENT_RESPONSE, $Event);
			sys::cmd()->onEnd($Req->CMD());
			$Res->send();
		} catch (\Exception $Ex) {
			Tracer::onException($Ex);
			$Event->setException($Ex);
			sys::event()->trigger(Event::EVENT_EXCEPTION, $Event);
			sys::cmd()->onException($Req->CMD());
		}
	}

	/**
	 * Resolve configured Controller to handle current Request
	 * @param Request $Req
	 * @param Response $Res
	 * @return string Controller ID
	 * @throws Exception
	 */
	protected function doRoute(Request $Req, Response $Res) {
		foreach ($this->routes as $cmd => $controllerID) {
			if (0 === strpos($Req->getAttribute('APP_MOD_URI'), $cmd)) {
				sys::trace(LOG_DEBUG, T_INFO, 'matched CMD: ' . $cmd . ' => Controller: ' . $controllerID, null, $this->_ . '->' . __FUNCTION__);
				$Req->setAttribute('APP_MOD_CONTROLLER', $controllerID);
				$Req->setAttribute('APP_MOD_CONTROLLER_URI', trim(substr($Req->getAttribute('APP_MOD_URI'), strlen($cmd))));
				return $controllerID;
			}
		}
		$Res->setExitStatus(404);
		throw new Exception(11, [$Req->getAttribute('APP_MOD_URI')]);
	}

	/**
	 * Resolve View name into an instantiated View object with template
	 * @param string $view
	 * @param Request $Req
	 * @param Response $Res
	 * @return array
	 * @throws \Exception
	 */
	protected function resolveView(string $view, Request $Req, Response $Res): array {
		try {
			preg_match('/^([a-z-]+:)?([^:\s]+)?$/', $view, $matches);
			@list(, $engine, $resource) = $matches;
			$engine                     = (empty($engine)) ? $this->defaultViewEngine : substr($engine, 0, -1);
			if (!empty($resource)) {
				$resource = (substr($resource, 0, 1) != '/') ? dirname('/' . str_replace(' ', '/', $Req->getAttribute('APP_MOD_URI'))) . '/' . $resource : $resource;
				$Req->setAttribute('RESOURCES_DIR', rtrim(preg_replace('/[\w-]+\/\.\.\//', '', (substr($this->resourcesDir, 0, 1) != '/') ? $Req->getAttribute('APP_MOD_DIR') . $this->resourcesDir : $this->resourcesDir), '/'));
			}
			if (!isset($this->viewEngines[$engine])) {
				throw new Exception(12, [$view, $resource]);
			}
			sys::trace(LOG_DEBUG, T_INFO, sprintf('view "%s", resource "%s"', $view, $resource), null, $this->_ . '->' . __FUNCTION__);
			$class = $this->viewEngines[$engine];
			$View  = new $class();
			return [$View, $resource];
		} catch (\Exception $Ex) {
			$Res->setExitStatus(500);
			throw $Ex;
		}
	}
}
