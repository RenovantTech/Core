<?php
namespace renovant\core\http\controller;
use const renovant\core\trace\T_INFO;
use renovant\core\sys,
	renovant\core\auth\Auth,
	renovant\core\http\Request,
	renovant\core\http\Response,
	renovant\core\http\Exception;
abstract class ActionController implements \renovant\core\http\ControllerInterface {
	use \renovant\core\CoreTrait;

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
	 * @throws Exception|\ReflectionException
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
						case Auth::class: $args[$i] = Auth::instance(); break;
						default: $args[$i] = new $param['class']($Req);
					}
				} elseif (isset($param['type'])) {
					switch($param['type']) {
						case 'boolean': $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default']: (boolean) $v; break;
						case 'int': $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default']: (integer) $v; break;
						case 'float': $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default']: (float) $v; break;
						case 'string': $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default']: (string) $v; break;
						case 'array': $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default']: (array) $v; break;
						default: $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default']: $v;
					}
				}
			}
		}
		$prevTraceFn = sys::traceFn($this->_.'->'.$action);
		try {
			// AUTHZ check
			method_exists($this, '_authz') and $this->_authz($action, $args);
			sys::trace(LOG_DEBUG, T_INFO);
			call_user_func_array([$this, $action], $args);
			$this->postHandle($Req, $Res);
		} finally {
			sys::traceFn($prevTraceFn);
		}
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
				($params['method'] == '*' || $params['method'] == $Req->getMethod())
				&&
				preg_match($params['pattern'], $Req->getAttribute('APP_MOD_CONTROLLER_URI'), $matches)
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
		throw new Exception(111, [$this->_, $action]);
	}
}
