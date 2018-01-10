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
	metadigit\core\auth\AUTH,
	metadigit\core\container\ContainerException,
	metadigit\core\http\Request,
	metadigit\core\http\Response,
	metadigit\core\http\Exception;
/**
 * Convenient superclass for controller implementations.
 * It adds interception methods and automatic request parameters on method signature.
 * Implementation classes must implement a doHandle() method.
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
abstract class AbstractController implements \metadigit\core\http\ControllerInterface {
	use \metadigit\core\CoreTrait;
	const ACL_SKIP = true;

	/** Controller handle method metadata (routing, params)
	 * @var array */
	protected $_config = [];
	/** default View engine
	 * @var string */
	protected $viewEngine = null;

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
	 * @throws ContainerException
	 */
	function handle(Request $Req, Response $Res) {
		if($this->viewEngine) $Res->setView(null, null, $this->viewEngine);
		if(true!==$this->preHandle($Req, $Res)) {
			sys::trace(LOG_DEBUG, T_INFO, 'FALSE returned, skip Request handling', null, $this->_.'->preHandle');
			return;
		}
		// inject URL params into Request
		if(isset($this->_config['route'])) {
			if(preg_match($this->_config['route'], $Req->URI(), $matches)) {
				foreach($matches as $k=>$v) {
					if(is_string($k)) $Req->set($k, $v);
				}
			}
		}
		$args = [];
		if(isset($this->_config['params'])) {
			foreach($this->_config['params'] as $i => $param) {
				if(!is_null($param['class'])) {
					switch ($param['class']) {
						case Request::class: $args[$i] = $Req; break;
						case Response::class: $args[$i] = $Res; break;
						case AUTH::class: $args[$i] = sys::auth(); break;
						default: $args[$i] = new $param['class']($Req);
					}
				} elseif (isset($param['type'])) {
					switch($param['type']) {
						case 'boolean': $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default']: (boolean) $v; break;
						case 'integer': $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default']: (integer) $v; break;
						case 'string': $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default']: (string) $v; break;
						case 'array': $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default']: (array) $v; break;
						default: $args[$i] = (is_null($v = $Req->get($param['name']))) ? null: $v;
					}
				}
			}
		}
		$prevTraceFn = sys::traceFn($this->_.'->doHandle');
		try {
			sys::trace(LOG_DEBUG, T_INFO);
			call_user_func_array([$this,'doHandle'], $args);
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
}
