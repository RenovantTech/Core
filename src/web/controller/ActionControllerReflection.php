<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\web\controller;
use metadigit\core\web\Exception,
	metadigit\core\util\reflection\ReflectionClass;
/**
 * Utility class for ActionController
 * @internal
 * @author Daniele Sciacchitano <dan@metadigit.it>
 */
class ActionControllerReflection {

	/**
	 * Return Controller's actions metadata
	 * @param ActionController $Controller
	 * @return array
	 * @throws Exception
	 */
	static function analyzeActions(ActionController $Controller) {
		// check implementation methods signature
		$actions = $routes = [];
		$ReflClass = new ReflectionClass($Controller);
		$reflMethods = $ReflClass->getMethods();
		foreach($reflMethods as $ReflMethod) {
			$methodName = $ReflMethod->getName();
			$methodClass = $ReflMethod->getDeclaringClass()->getName();
			// check signature of preHanlde & postHanlde hooks
			if(in_array($methodName, ['preHandle','postHandle'])) {
				if(!$ReflMethod->isProtected()) throw new Exception(101, [$methodClass,$methodName]);
			// check signature of handling methods (skip protected/private methods, they can't be handler!)
			} elseif($ReflMethod->isPublic() && substr($methodName,-6)=='Action') {
				$action = substr($methodName,0,-6);
				$actions[$action] = [];
				// routing
				$DocComment = $ReflMethod->getDocComment();
				if($DocComment->hasTag('routing')) {
					$tag = $DocComment->getTag('routing');
					$routes[$action]['method'] = isset($tag['method']) ? $tag['method'] : '*';
					if(isset($tag['pattern'])) {
						$pattern = str_replace('/', '\/', $tag['pattern']);
						$pattern = preg_replace('/<(\w+)>/', '(?<$1>[^\/]+)', $pattern);
						$pattern = preg_replace('/<(\w+):([^>]+)>/', '(?<$1>$2)', $pattern);
						$routes[$action]['pattern'] = '/'.$pattern.'$/';
					} else $routes[$action]['pattern'] = '/'.$action.'$/';
				} else {
					$routes[$action]['method'] = '*';
					if($action == constant(get_class($Controller).'::DEFAULT_ACTION')) $routes[$action]['pattern'] = '/\/$/';
					else $routes[$action]['pattern'] = '/'.$action.'$/';
				}
				// parameters
				foreach($ReflMethod->getParameters() as $i => $ReflParam) {
					switch($i){
						case 0:
							if(!$ReflParam->getClass()->getName() == 'metadigit\core\http\Request')
								throw new Exception(102, [$methodClass,$methodName,$i+1,'metadigit\core\http\Request']);
							break;
						case 1:
							if(!$ReflParam->getClass()->getName() == 'metadigit\core\http\Response')
								throw new Exception(102, [$methodClass,$methodName,$i+1,'metadigit\core\http\Response']);
							break;
						default:
							$actions[$action]['params'][$i]['name'] = $ReflParam->getName();
							$actions[$action]['params'][$i]['class'] = (!is_null($ReflParam->getClass())) ? $ReflParam->getClass()->getName() : null;
							$actions[$action]['params'][$i]['type'] = $ReflParam->getType();
							$actions[$action]['params'][$i]['optional'] = $ReflParam->isOptional();
							$actions[$action]['params'][$i]['default'] = ($ReflParam->isDefaultValueAvailable()) ? $ReflParam->getDefaultValue() : null;
					}
				}
			}
		}
		return [$actions, $routes];
	}
}
