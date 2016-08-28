<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\web;
use function metadigit\core\trace;
use metadigit\core\KernelDebugger,
	metadigit\core\http\Request,
	metadigit\core\http\Response;
/**
 * High speed implementation of HTTP Dispatcher based on URLs.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class Dispatcher {
	use \metadigit\core\CoreTrait;

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
		'file'		=> 'metadigit\core\web\view\FileView',
		'file-csv'	=> 'metadigit\core\web\view\CsvView',
		'file-excel'=> 'metadigit\core\web\view\ExcelView',
		'json'		=> 'metadigit\core\web\view\JsonView',
		'php'		=> 'metadigit\core\web\view\PhpView',
		'phptal'	=> 'metadigit\core\web\view\PhpTALView',
//		'smarty'	=> 'metadigit\core\web\view\SmartyView',
//		'twig'		=> 'metadigit\core\web\view\TwigView',
		'xsendfile'	=> 'metadigit\core\web\view\XSendFileView'
	];

	function dispatch(Request $Req, Response $Res) {
		$Controller = $resource = null;
		$DispatcherEvent = new DispatcherEvent($Req, $Res);
		try {
			if(!$this->context()->trigger(DispatcherEvent::EVENT_ROUTE, $this, [], $DispatcherEvent)->isPropagationStopped()) {
				$Controller = $this->context()->get($this->resolveController($Req), 'metadigit\core\web\ControllerInterface');
				$DispatcherEvent->setController($Controller);
			}
			if($Controller) {
				if(!$this->context()->trigger(DispatcherEvent::EVENT_CONTROLLER, $this, [], $DispatcherEvent)->isPropagationStopped()) {
					$Controller->handle($Req, $Res);
				}
			}
			if($View = $Res->getView() ?: $DispatcherEvent->getView()) {
				if(is_string($View)) list($View, $resource) = $this->resolveView($View, $Req);
				if(!$View instanceof ViewInterface) throw new Exception(13);
				$DispatcherEvent->setView($View);
				if(!$this->context()->trigger(DispatcherEvent::EVENT_VIEW, $this, [], $DispatcherEvent)->isPropagationStopped()) {
					$View->render($Req, $Res, $resource);
				}
			}
			$this->context()->trigger(DispatcherEvent::EVENT_RESPONSE, $this, [], $DispatcherEvent);
		} catch(\Exception $Ex) {
			$DispatcherEvent->setException($Ex);
			$this->context()->trigger(DispatcherEvent::EVENT_EXCEPTION, $this, [], $DispatcherEvent);
			if(200 == http_response_code()) http_response_code(500);
			KernelDebugger::onException($Ex);
		}
		$Res->send();
	}

	/**
	 * Resolve configured Controller to handle current Request
	 * @param Request $Req
	 * @return string Controller ID
	 * @throws Exception
	 */
	protected function resolveController(Request $Req) {
		foreach($this->routes as $url => $controllerID) {
			if(fnmatch($url, $Req->getAttribute('APP_URI'))) {
				TRACE and trace(LOG_DEBUG, TRACE_DEFAULT, $url.' => '.$controllerID, null, $this->_oid.'->'.__FUNCTION__);
				$Req->setAttribute('APP_CONTROLLER', $controllerID);
				return $controllerID;
			}
		}
		http_response_code(404);
		throw new Exception(11, [$Req->getAttribute('APP_URI')]);
	}

	/**
	 * Resolve View name into an instantiated View object with template
	 * @param string $view
	 * @param Request $Req
	 * @return array
	 * @throws \Exception
	 */
	protected function resolveView($view, Request $Req) {
		try {
			preg_match('/^([a-z-]+:)?([^:\s]+)?$/', $view, $matches);
			@list($_, $engine, $resource) = $matches;
			$engine = (empty($engine)) ? $this->defaultViewEngine : substr($engine,0,-1);
			if(!empty($resource)) {
				$resource = str_replace('//','/', (substr($resource,0,1) != '/' ) ? dirname($Req->getAttribute('APP_URI').'*').'/'.$resource : $resource);
				$Req->setAttribute('RESOURCES_DIR', rtrim(preg_replace('/[\w-]+\/\.\.\//', '', (substr($this->resourcesDir,0,1) != '/' ) ? $Req->getAttribute('APP_DIR').$this->resourcesDir : $this->resourcesDir), '/'));
			}
			if(!isset($this->viewEngines[$engine])) throw new Exception(12, [$view, $resource]);
			TRACE and trace(LOG_DEBUG, TRACE_DEFAULT, sprintf('view "%s", resource "%s"', $view, $resource), null, $this->_oid.'->'.__FUNCTION__);
			$class = $this->viewEngines[$engine];
			$View = new $class;
			return [$View, $resource];
		} catch (\Exception $Ex) {
			http_response_code(500);
			throw $Ex;
		}
	}
}
