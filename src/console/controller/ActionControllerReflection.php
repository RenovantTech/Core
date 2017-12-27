<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\console\controller;
use metadigit\core\console\Exception,
	metadigit\core\console\Request,
	metadigit\core\console\Response,
	metadigit\core\util\reflection\ReflectionClass;
/**
 * Utility class for ActionController
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
		$actions = [];
		$RefClass = new ReflectionClass($Controller);
		$refMethods = $RefClass->getMethods();
		foreach($refMethods as $RefMethod) {
			$methodName = $RefMethod->getName();
			$methodClass = $RefMethod->getDeclaringClass()->getName();
			// check signature of preHanlde & postHanlde hooks
			if(in_array($methodName, ['preHandle','postHandle'])) {
				if(!$RefMethod->isProtected()) throw new Exception(101, [$methodClass,$methodName]);
			// check signature of handling methods (skip protected/private methods, they can't be handler!)
			} elseif($RefMethod->isPublic() && substr($methodName,-6)=='Action') {
				$action = substr($methodName,0,-6);
				$actions[$action] = [];
				foreach($RefMethod->getParameters() as $i => $RefParam) {
					switch($i){
						case 0:
							if(!$RefParam->getClass()->getName() == Request::class)
								throw new Exception(102, [$methodClass, $methodName, $i+1, Request::class]);
							break;
						case 1:
							if(!$RefParam->getClass()->getName() == Response::class)
								throw new Exception(102, [$methodClass, $methodName, $i+1, Response::class]);
							break;
						default:
							$actions[$action]['params'][$i]['name'] = $RefParam->getName();
							$actions[$action]['params'][$i]['class'] = (!is_null($RefParam->getClass())) ? $RefParam->getClass()->getName() : null;
							$actions[$action]['params'][$i]['type'] = $RefParam->getType();
							$actions[$action]['params'][$i]['optional'] = $RefParam->isOptional();
							$actions[$action]['params'][$i]['default'] = ($RefParam->isDefaultValueAvailable()) ? $RefParam->getDefaultValue() : null;
					}
				}
			}
		}
		return $actions;
	}
}
