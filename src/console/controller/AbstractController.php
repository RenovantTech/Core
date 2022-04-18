<?php
namespace renovant\core\console\controller;
use const renovant\core\trace\T_INFO;
use renovant\core\sys,
	renovant\core\console\Request,
	renovant\core\console\Response,
	renovant\core\console\Exception;
/**
 * Convenient superclass for controller implementations.
 * It adds interception methods and automatic request parameters on method signature.
 * Implementation classes must implement a doHandle() method.
 */
abstract class AbstractController implements \renovant\core\console\ControllerInterface {
	use \renovant\core\CoreTrait;

	/** Controller handle method configuration
	 * @var array */
	protected $_config = [];

	/**
	 * AbstractController constructor.
	 * @throws Exception
	 */
	function __construct() {
		$this->_config = AbstractControllerReflection::analyzeHandle($this);
	}

	/**
	 * @param Request $Req
	 * @param Response $Res
	 * @return \renovant\core\console\ViewInterface|mixed|null|string
	 */
	function handle(Request $Req, Response $Res) {
		if(true!==$this->preHandle($Req, $Res)) {
			sys::trace(LOG_DEBUG, T_INFO, 'FALSE returned, skip Request handling', null, $this->_.'->preHandle');
			return null;
		}
		$args = [];
		if(isset($this->_config['params'])) {
			foreach($this->_config['params'] as $i => $param) {
				if(!is_null($param['class'])) {
					switch ($param['class']) {
						case Request::class: $args[$i] = $Req; break;
						case Response::class: $args[$i] = $Res; break;
						default: $args[$i] = new $param['class']($Req);
					}
				} elseif (isset($param['type'])) {
					switch($param['type']) {
						case 'boolean': $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default']: (boolean) $v; break;
						case 'int': $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default']: (integer) $v; break;
						case 'string': $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default']: (string) $v; break;
						case 'array': $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default']: (array) $v; break;
						default: $args[$i] = (is_null($v = $Req->get($param['name']))) ? null: $v;
					}
				}
			}
		}
		$prevTraceFn = sys::traceFn($this->_ . '->doHandle');
		try {
			sys::trace(LOG_DEBUG, T_INFO);
			$View = call_user_func_array([$this, 'doHandle'], $args);
			$this->postHandle($Req, $Res, $View);
			return $View;
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
	 * @param \renovant\core\http\ViewInterface|string $View the View or view name
	 */
	protected function postHandle(Request $Req, Response $Res, $View=null) {
	}
}
