<?php
namespace renovant\core\http;
use const renovant\core\trace\T_INFO;
use renovant\core\sys,
	renovant\core\auth\AuthException,
	renovant\core\authz\AuthzException,
	renovant\core\http\view\FileView,
	renovant\core\http\view\CsvView,
	renovant\core\http\view\ExcelView,
	renovant\core\http\view\JsonView,
	renovant\core\http\view\PhpView,
	renovant\core\http\view\PhpTALView,
//	renovant\core\http\view\SmartyView,
//	renovant\core\http\view\TwigView,
	renovant\core\http\view\XSendFileView,
	renovant\core\trace\Tracer;
class Dispatcher {
	use \renovant\core\CoreTrait;

	/** Array of routes between Request URLs and Controllers names.
	 * @var array */
	protected $routes = [];
	/** customizable templates dir path, default to \renovant\core\PUBLIC_DIR
	 * @var string */
	protected $resourcesDir = \renovant\core\PUBLIC_DIR;
	/** default View engine
	 * @var string */
	protected $viewEngine = null;
	/** View engines mapping
	 * @var array */
	protected $viewEngines = [
		ENGINE_FILE			=> FileView::class,
		ENGINE_FILE_CSV		=> CsvView::class,
		ENGINE_FILE_EXCEL	=> ExcelView::class,
		ENGINE_JSON			=> JsonView::class,
		ENGINE_PHP			=> PhpView::class,
		ENGINE_PHP_TAL		=> PhpTALView::class,
//		ENGINE_SMARTY		=> SmartyView::class,
//		ENGINE_TWIG			=> TwigView::class,
		ENGINE_X_SEND_FILE	=> XSendFileView::class
	];

	function __construct() {
		include __DIR__.'/Dispatcher.constructor.inc';
	}

	/**
	 * @param Request $Req
	 * @param Response $Res
	 * @throws \ReflectionException
	 * @throws \renovant\core\context\ContextException
	 * @throws \renovant\core\event\EventDispatcherException
	 */
	function dispatch(Request $Req, Response $Res) {
		ob_start(null, 0, PHP_OUTPUT_HANDLER_STDFLAGS);
		$Controller = null;
		$DispatcherEvent = new Event($Req, $Res);
		try {
			if (!sys::event()->trigger(Event::EVENT_ROUTE, $DispatcherEvent)->isPropagationStopped()) {
				$Controller = sys::context()->get($this->doRoute($Req), ControllerInterface::class);
				$DispatcherEvent->setController($Controller);
			}
			if ($Controller) {
				$Res->setView(null, null, $this->viewEngine);
				if (!sys::event()->trigger(Event::EVENT_CONTROLLER, $DispatcherEvent)->isPropagationStopped()) {
					$Controller->handle($Req, $Res);
				}
			}
			list($View, $viewResource, $viewOptions) = $this->resolveView($Req, $Res, $DispatcherEvent);
			if ($View) {
				if (!sys::event()->trigger(Event::EVENT_VIEW, $DispatcherEvent)->isPropagationStopped()) {
					$View->render($Req, $Res, $viewResource, $viewOptions);
				}
			}
			sys::event()->trigger(Event::EVENT_RESPONSE, $DispatcherEvent);
			$Res->send();
		} catch (AuthException $Ex) {
			$DispatcherEvent->setException($Ex);
			sys::event()->trigger(Event::EVENT_EXCEPTION, $DispatcherEvent);
			http_response_code(401);
		} catch (AuthzException $Ex) {
			$DispatcherEvent->setException($Ex);
			sys::event()->trigger(Event::EVENT_EXCEPTION, $DispatcherEvent);
			http_response_code(403);
		} catch(\Exception $Ex) {
			$DispatcherEvent->setException($Ex);
			sys::event()->trigger(Event::EVENT_EXCEPTION, $DispatcherEvent);
			if(200 == http_response_code()) http_response_code(500);
			Tracer::onException($Ex);
		} finally {
			if(!empty(ob_get_status())) ob_end_clean();
		}
	}

	/**
	 * Resolve configured Controller to handle current Request
	 * @param Request $Req
	 * @return string Controller ID
	 * @throws Exception
	 */
	protected function doRoute(Request $Req) {
		$url = $Req->getAttribute('APP_MOD_URI');
		foreach($this->routes as $pattern => $controllerID) {
			if(preg_match($pattern, $url, $matches))	 {
				sys::trace(LOG_DEBUG, T_INFO, 'URL: '.$url.' matching pattern '.$pattern.' => Controller: '.$controllerID, null, $this->_.'->'.__FUNCTION__);
				$Req->setAttribute('APP_MOD_CONTROLLER', $controllerID);
				$Req->setAttribute('APP_MOD_CONTROLLER_URI', substr($Req->getAttribute('APP_MOD_URI'), strlen($matches[0])));
				// inject URL params into Request
				foreach($matches as $k=>$v) {
					if(is_string($k)) $Req->set($k, $v);
				}
				return $controllerID;
			}
		}
		http_response_code(404);
		throw new Exception(11, [$Req->getAttribute('APP_MOD_URI')]);
	}

	/**
	 * Resolve View name into an instantiated View object with template
	 * @param Request $Req
	 * @param Response $Res
	 * @param Event $Event
	 * @return array $View, $resource, $viewOptions
	 * @throws \Exception
	 */
	protected function resolveView(Request $Req, Response $Res, Event $Event) {
		try {
			list($view, $viewOptions, $viewEngine) = $Res->getView() ?: $Event->getView();
			if(!$viewEngine) return [null, null, null];
			// detect View class
			$viewClass = (array_key_exists($viewEngine, $this->viewEngines)) ? $this->viewEngines[$viewEngine] : $viewEngine;
			if(!class_exists($viewClass) || $viewClass instanceof ViewInterface) throw new Exception(12, $viewEngine);
			$View = new $viewClass;
			$Event->setView($View);
			// detect resource
			if(!empty($view)) {
				$resource = str_replace('//','/', (substr($view,0,1) != '/' ) ? dirname($Req->getAttribute('APP_MOD_URI').'*').'/'.$view : $view);
				$Req->setAttribute('RESOURCES_DIR', rtrim(preg_replace('/[\w-]+\/\.\.\//', '', (substr($this->resourcesDir,0,1) != '/' ) ? $Req->getAttribute('APP_MOD_DIR').$this->resourcesDir : $this->resourcesDir), '/'));
			} else $resource = null;
			sys::trace(LOG_DEBUG, T_INFO, sprintf('view "%s", resource "%s"', $view, $resource), null, $this->_.'->'.__FUNCTION__);
			return [$View, $resource, $viewOptions];
		} catch (\Exception $Ex) {
			http_response_code(500);
			throw $Ex;
		}
	}
}
