<?php
/**
 * Metadigit Core PHP Framework
 * @link http://github.com/Metadigit/Core
 * @copyright Copyright (c) 2004-2014 Daniele Sciacchitano <dan@metadigit.it>
 * @license New BSD License
 */
namespace metadigit\core\console\controller;
use metadigit\core\console\Exception,
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
				foreach($ReflMethod->getParameters() as $i => $ReflParam) {
					switch($i){
						case 0:
							if(!$ReflParam->getClass()->getName() == 'metadigit\core\Request')
								throw new Exception(102, [$methodClass,$methodName,$i+1,'metadigit\core\Request']);
							break;
						case 1:
							if(!$ReflParam->getClass()->getName() == 'metadigit\core\Response')
								throw new Exception(102, [$methodClass,$methodName,$i+1,'metadigit\core\Response']);
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
		return $actions;
	}
}
