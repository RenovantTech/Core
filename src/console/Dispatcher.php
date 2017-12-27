<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\console;
use const metadigit\core\trace\T_INFO;
use metadigit\core\sys,
	metadigit\core\cli\Request,
	metadigit\core\cli\Response,
	metadigit\core\trace\Tracer;
/**
 * High speed implementation of CLI Dispatcher based on plain args.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Dispatcher {
	use \metadigit\core\CoreTrait;
	const ACL_SKIP = true;

	/** default View engine
	 * @var string */
	protected $defaultViewEngine = 'php';
	/** Array of routes between Request URLs and Controllers names.
	 * @var array */
	protected $routes = [];
	/** customizable templates dir path, default to \metadigit\core\PUBLIC_DIR
	 * @var string */
	protected $resourcesDir = \metadigit\core\PUBLIC_DIR;
	/** View engines mapping
	 * @var array */
	protected $viewEngines = [
		'php'		=> 'metadigit\core\console\view\PhpView'
//		'smarty'	=> 'metadigit\core\console\view\SmartyView'
//		'twig'		=> 'metadigit\core\console\view\TwigView'
	];

	function dispatch(Request $Req, Response $Res) {
		$Controller = $resource = null;
		$DispatcherEvent = new DispatcherEvent($Req, $Res);
		try {
			if(!sys::event(DispatcherEvent::EVENT_ROUTE, $DispatcherEvent)->isPropagationStopped()) {
				$Controller = sys::context()->get($this->doRoute($Req, $Res), 'metadigit\core\console\ControllerInterface');
				$DispatcherEvent->setController($Controller);
			}
			if($Controller) {
				if(!sys::event(DispatcherEvent::EVENT_CONTROLLER, $DispatcherEvent)->isPropagationStopped()) {
					$Controller->handle($Req, $Res);
				}
			}
			if($View = $Res->getView() ?: $DispatcherEvent->getView()) {
				if(is_string($View)) list($View, $resource) = $this->resolveView($View, $Req, $Res);
				if(!$View instanceof ViewInterface) throw new Exception(13);
				$DispatcherEvent->setView($View);
				if(!sys::event(DispatcherEvent::EVENT_VIEW, $DispatcherEvent)->isPropagationStopped()) {
					$View->render($Req, $Res, $resource);
				}
			}
			sys::event(DispatcherEvent::EVENT_RESPONSE, $DispatcherEvent);
		} catch(\Exception $Ex) {
			$DispatcherEvent->setException($Ex);
			sys::event(DispatcherEvent::EVENT_EXCEPTION, $DispatcherEvent);
			Tracer::onException($Ex);
		}
		$Res->send();
	}

	/**
	 * Resolve configured Controller to handle current Request
	 * @param Request $Req
	 * @param Response $Res
	 * @return string Controller ID
	 * @throws Exception
	 */
	protected function doRoute(Request $Req, Response $Res) {
		foreach($this->routes as $cmd => $controllerID) {
			if(0===strpos($Req->getAttribute('APP_URI'), $cmd)) {
				sys::trace(LOG_DEBUG, T_INFO, 'matched CMD: '.$cmd.' => Controller: '.$controllerID, null, $this->_.'->'.__FUNCTION__);
				$Req->setAttribute('APP_CONTROLLER', $controllerID);
				return $controllerID;
			}
		}
		$Res->setExitStatus(404);
		throw new Exception(11, [$Req->getAttribute('APP_URI')]);
	}

	/**
	 * Resolve View name into an instantiated View object with template
	 * @param string $view
	 * @param Request $Req
	 * @param Response $Res
	 * @return array
	 * @throws \Exception
	 */
	protected function resolveView($view, Request $Req, Response $Res) {
		try {
			preg_match('/^([a-z-]+:)?([^:\s]+)?$/', $view, $matches);
			@list($_, $engine, $resource) = $matches;
			$engine = (empty($engine)) ? $this->defaultViewEngine : substr($engine,0,-1);
			if(!empty($resource)) {
				$resource = (substr($resource,0,1) != '/' ) ? dirname('/'.str_replace(' ','/',$Req->getAttribute('APP_URI'))).'/'.$resource : $resource;
				$Req->setAttribute('RESOURCES_DIR', rtrim(preg_replace('/[\w-]+\/\.\.\//', '', (substr($this->resourcesDir,0,1) != '/' ) ? $Req->getAttribute('APP_DIR').$this->resourcesDir : $this->resourcesDir), '/'));
			}
			if(!isset($this->viewEngines[$engine])) throw new Exception(12, [$view, $resource]);
			sys::trace(LOG_DEBUG, T_INFO, sprintf('view "%s", resource "%s"', $view, $resource), null, $this->_.'->'.__FUNCTION__);
			$class = $this->viewEngines[$engine];
			$View = new $class;
			return [$View, $resource];
		} catch (\Exception $Ex) {
			$Res->setExitStatus(500);
			throw $Ex;
		}
	}
}
