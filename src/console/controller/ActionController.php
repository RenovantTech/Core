<?php
namespace renovant\core\console\controller;

use renovant\core\sys;
use renovant\core\console\{Exception, Request, Response};
use renovant\core\util\str;

use const renovant\core\trace\T_INFO;

abstract class ActionController implements \renovant\core\console\ControllerInterface {
	use \renovant\core\CoreTrait;

	/** Default action method to invoke. */
	public const DEFAULT_ACTION = 'index';
	/** Fallback action method to invoke. */
	public const FALLBACK_ACTION = null;
	/** Controller actions metadata (routing, params)
	 * @var array */
	protected $_config = [];

	/**
	 * ActionController constructor.
	 * @throws Exception|\ReflectionException
	 */
	public function __construct() {
		$this->_config = ActionControllerReflection::analyzeActions($this);
	}

	/**
	 * @param Request $Req
	 * @param Response $Res
	 * @throws Exception
	 */
	public function handle(Request $Req, Response $Res) {
		$action = $this->resolveActionMethod($Req);
		if (true !== $this->preHandle($Req, $Res)) {
			sys::trace(LOG_DEBUG, T_INFO, 'FALSE returned, skip Request handling', null, $this->_ . '->preHandle');
			return;
		}
		$args = [];
		if (isset($this->_config[$action]['params'])) {
			foreach ($this->_config[$action]['params'] as $i => $param) {
				if (!is_null($param['class'])) {
					switch ($param['class']) {
						case Request::class: $args[$i] = $Req;
							break;
						case Response::class: $args[$i] = $Res;
							break;
						default: $args[$i] = new $param['class']($Req);
					}
				} elseif (isset($param['type'])) {
					switch ($param['type']) {
						case 'boolean': $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default'] : (bool) $v;
							break;
						case 'int': $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default'] : (int) $v;
							break;
						case 'float': $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default'] : (float) $v;
							break;
						case 'string': $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default'] : (string) $v;
							break;
						case 'array': $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default'] : (array) $v;
							break;
						default: $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default'] : $v;
					}
				}
			}
		}
		$prevTraceFn = sys::traceFn($this->_ . '->' . $action);
		try {
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
		$action = str::kebab2camel(substr(strrchr($Req->CMD(), ' '), 1));
		if (empty($action)) {
			$action = self::DEFAULT_ACTION;
		}
		if (isset($this->_config[$action])) {
			return $action;
		}
		if (isset($this->_config[$this::FALLBACK_ACTION])) {
			return $this::FALLBACK_ACTION;
		}
		http_response_code(404);
		throw new Exception(111, [$this->_, $action]);
	}
}
