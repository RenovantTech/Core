<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\console\controller;
use function metadigit\core\trace;
use metadigit\core\Kernel,
	metadigit\core\cli\Request,
	metadigit\core\cli\Response,
	metadigit\core\console\Exception;
/**
 * MVC action Controller implementation.
 * Allows multiple requests types (aka action) to be handled by the same Controller class.
 * Action methods must have the following signature:
 * <code>
 * function exampleAction(Request $Req, Response $Res)
 * </code>
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
abstract class ActionController implements \metadigit\core\console\ControllerInterface {
	use \metadigit\core\CoreTrait;
	const ACL_SKIP = true;

	/** Default action method to invoke. */
	const DEFAULT_ACTION = 'index';
	/** Fallback action method to invoke. */
	const FALLBACK_ACTION = null;
	/** Controller actions methods configuration
	 * @var array */
	protected $_actions = [];

	function __construct() {
		$this->_actions = ActionControllerReflection::analyzeActions($this);
	}

	function handle(Request $Req, Response $Res) {
		$action = $this->resolveActionMethod($Req);
		if(true!==$this->preHandle($Req, $Res)) {
			TRACE and trace(LOG_DEBUG, TRACE_DEFAULT, 'FALSE returned, skip Request handling', null, $this->_oid.'->preHandle');
			return null;
		}
		$args = array($Req, $Res);
		if(isset($this->_actions[$action]['params'])) {
			TRACE and trace(LOG_DEBUG, TRACE_DEFAULT, 'building action params');
			foreach($this->_actions[$action]['params'] as $i => $param) {
				if(!is_null($param['class'])) {
					$paramClass = $param['class'];
					$args[$i] = new $paramClass($Req);
				} elseif (isset($param['type'])) {
					switch($param['type']) {
						case 'boolean': $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default']: (boolean) $v; break;
						case 'integer': $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default']: (integer) $v; break;
						case 'string': $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default']: (string) $v; break;
						case 'array': $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default']: (array) $v; break;
						default: $args[$i] = (is_null($v = $Req->get($param['name']))) ? $param['default']: $v;
					}
				}
			}
		}
		Kernel::traceFn($this->_oid.'->'.$action.'Action');
		TRACE and trace(LOG_DEBUG, TRACE_DEFAULT);
		$View = call_user_func_array([$this,$action.'Action'], $args);
		$this->postHandle($Req, $Res, $View);
		return $View;
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
	 * @param \metadigit\core\web\ViewInterface|string $View the View or view name
	 * @throws Exception in case of errors
	 */
	protected function postHandle(Request $Req, Response $Res, $View=null) {
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
		$action = str_replace(['-','.'], '_', $action);
		if(empty($action)) $action = self::DEFAULT_ACTION;
		if(isset($this->_actions[$action])) return $action;
		if(isset($this->_actions[$this::FALLBACK_ACTION])) return $this::FALLBACK_ACTION;
		http_response_code(404);
		throw new Exception(111, [$this->_oid, $action.'Action']);
	}
}
