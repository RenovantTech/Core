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
	metadigit\core\trace\Tracer,
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
	/** Controller actions methods configuration
	 * @var array */
	protected $_actions = [];
	/** Controller actions routing configuration
	 * @var array */
	protected $_routes = [];
	/** default View engine
	 * @var string */
	protected $viewEngine = null;

	function __construct() {
		list($this->_actions, $this->_routes) = ActionControllerReflection::analyzeActions($this);
	}

	function handle(Request $Req, Response $Res) {
		if($this->viewEngine) $Res->setView(null, null, $this->viewEngine);
		$action = $this->resolveActionMethod($Req);
		if(true!==$this->preHandle($Req, $Res)) {
			sys::trace(LOG_DEBUG, T_INFO, 'FALSE returned, skip Request handling', null, $this->_oid.'->preHandle');
			return null;
		}
		$args = [$Req, $Res];
		if(isset($this->_actions[$action]['params'])) {
			foreach($this->_actions[$action]['params'] as $i => $param) {
				if(!is_null($param['class'])) {
					$paramClass = $param['class'];
					$args[$i] = new $paramClass($Req);
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
		Tracer::traceFn($this->_oid.'->'.$action.'Action');
		sys::trace(LOG_DEBUG, T_INFO);
		call_user_func_array([$this, $action.'Action'], $args);
		$this->postHandle($Req, $Res);
	}

	/**
	 * Pre-handle hook, can be overridden by subclasses.
	 * @param Request $Req current request
	 * @param Response $Res current response
	 * @return boolean TRUE on success, FALSE on error
	 * @throws Exception in case of errors
	 */
	protected function preHandle(Request $Req, Response $Res) {
		return true;
	}

	/**
	 * Post-handle hook, can be overridden by subclasses.
	 * @param Request $Req current request
	 * @param Response $Res current response
	 * @throws Exception in case of errors
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
		foreach($this->_routes as $actioName=>$params) {
			if(
				($params['method'] == '*' || $params['method'] == $_SERVER['REQUEST_METHOD'])
				&&
				preg_match($params['pattern'], $Req->URI(), $matches)
			) {
				foreach($matches as $k=>$v) {
					if(is_string($k)) $Req->set($k, $v);
				}
				$action = $actioName;
				break;
			}
		}
		if(isset($this->_actions[$action])) return $action;
		http_response_code(404);
		throw new Exception(111, [$this->_oid, $action.'Action']);
	}
}
