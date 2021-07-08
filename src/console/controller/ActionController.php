<?php
/**
 * Renovant Technology Core PHP Framework
 * @link https://github.com/RenovantTech/Core
 * @copyright Copyright (c) 2004-2018 Daniele Sciacchitano
 * @license New BSD License
 */
namespace renovant\core\console\controller;
use const renovant\core\trace\T_INFO;
use renovant\core\sys,
	renovant\core\console\Request,
	renovant\core\console\Response,
	renovant\core\console\Exception;
/**
 * MVC action Controller implementation.
 * Allows multiple requests types (aka action) to be handled by the same Controller class.
 * Action methods must have the following signature:
 * <code>
 * function exampleAction(Request $Req, Response $Res)
 * </code>
 * @author Daniele Sciacchitano <dan@renovant.tech>
 */
abstract class ActionController implements \renovant\core\console\ControllerInterface {
	use \renovant\core\CoreTrait;
	const ACL_SKIP = true;

	/** Default action method to invoke. */
	const DEFAULT_ACTION = 'index';
	/** Fallback action method to invoke. */
	const FALLBACK_ACTION = null;
	/** Controller actions metadata (routing, params)
	 * @var array */
	protected $_config = [];

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
		$prevTraceFn = sys::traceFn($this->_.'->'.$action);
		try {
			sys::trace(LOG_DEBUG, T_INFO);
			call_user_func_array([$this,$action], $args);
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
		$action = substr(strrchr($Req->CMD(), ' '), 1);
		// kebab to camel case
		$action = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $action))));
		if(empty($action)) $action = self::DEFAULT_ACTION;
		if(isset($this->_config[$action])) return $action;
		if(isset($this->_config[$this::FALLBACK_ACTION])) return $this::FALLBACK_ACTION;
		http_response_code(404);
		throw new Exception(111, [$this->_, $action]);
	}
}
