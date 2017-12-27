<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\http\controller;
use const metadigit\core\trace\T_INFO;
use metadigit\core\sys,
	metadigit\core\http\Request,
	metadigit\core\http\Response,
	metadigit\core\http\Exception;
/**
 * MVC action Controller implementation.
 * Allows multiple requests types (aka action) to be handled by the same Controller class.
 * Action methods must have the following signature:
 * <code>
 * function exampleAction(Request $Req, Response $Res)
 * </code>
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
abstract class ActionController implements \metadigit\core\http\ControllerInterface {
	use \metadigit\core\CoreTrait;
	const ACL_SKIP = true;

	/** Default action method to invoke. */
	const DEFAULT_ACTION = 'index';
	/** Controller actions metadata (routing, params)
	 * @var array */
	protected $_config = [];
	/** default View engine
	 * @var string */
	protected $viewEngine = null;

	/**
	 * ActionController constructor.
	 * @throws Exception
	 */
	function __construct() {
		$this->_config = ActionControllerReflection::analyzeActions($this);
	}

	/**
	 * @param Request $Req
	 * @param Response $Res
	 * @throws Exception
	 */
	function handle(Request $Req, Response $Res) {
		if($this->viewEngine) $Res->setView(null, null, $this->viewEngine);
		$action = $this->resolveActionMethod($Req);
		if(true!==$this->preHandle($Req, $Res)) {
			sys::trace(LOG_DEBUG, T_INFO, 'FALSE returned, skip Request handling', null, $this->_.'->preHandle');
			return;
		}
		$args = [];
		if(isset($this->_config[$action]['params'])) {
			foreach($this->_config[$action]['params'] as $i => $param) {
				if(!is_null($param['class'])) {
					switch ($param['class']) {
						case Request::class: $args[$i] = $Req; break;
						case Response::class: $args[$i] = $Res; break;
						default: $args[$i] = new $param['class']($Req);
					}
				} elseif (isset($param['type'])) {
					switch($param['type']) {
						case 'boolean': $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default']: (boolean) $v; break;
						case 'integer': $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default']: (integer) $v; break;
						case 'float': $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default']: (float) $v; break;
						case 'string': $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default']: (string) $v; break;
						case 'array': $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default']: (array) $v; break;
						default: $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default']: $v;
					}
				}
			}
		}
		sys::traceFn($this->_.'->'.$action.'Action');
		sys::trace(LOG_DEBUG, T_INFO);
		call_user_func_array([$this, $action.'Action'], $args);
		$this->postHandle($Req, $Res);
	}

	/**
	 * Pre-handle hook, can be overridden by subclasses.
	 * @param Request $Req current request
	 * @param Response $Res current response
	 * @return boolean TRUE on success, FALSE on error
	 */
	protected function preHandle(Request $Req, Response $Res) {
		return true;
	}

	/**
	 * Post-handle hook, can be overridden by subclasses.
	 * @param Request $Req current request
	 * @param Response $Res current response
	 */
	protected function postHandle(Request $Req, Response $Res) {
	}

	/**
	 * Return an action name that can handle this request. Subclasses can override this.
	 * Such mappings are typically, but not necessarily, based on URL.
	 * @param Request $Req current request
	 * @return string a method name that can handle this request. Never returns <code>null</code>; throws exception if not resolvable.
	 * @throws Exception if no handler method can be found for the given request
	 */
	protected function resolveActionMethod(Request $Req) {
		$action = null;
		foreach($this->_config as $actionName=>$params) {
			if(
				($params['method'] == '*' || $params['method'] == $_SERVER['REQUEST_METHOD'])
				&&
				preg_match($params['pattern'], $Req->URI(), $matches)
			) {
				foreach($matches as $k=>$v) {
					if(is_string($k)) $Req->set($k, $v);
				}
				$action = $actionName;
				break;
			}
		}
		if(isset($this->_config[$action])) return $action;
		http_response_code(404);
		throw new Exception(111, [$this->_, $action.'Action']);
	}
}
