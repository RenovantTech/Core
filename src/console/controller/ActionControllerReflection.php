<?php
namespace renovant\core\console\controller;
use renovant\core\console\Exception,
	renovant\core\util\reflection\ReflectionClass;
class ActionControllerReflection {

	/**
	 * Return Controller's actions metadata
	 * @param ActionController $Controller
	 * @return array
	 * @throws Exception|\ReflectionException
	 */
	static function analyzeActions(ActionController $Controller) {
		$config = [];
		$RefClass = new ReflectionClass($Controller);
		$refMethods = $RefClass->getMethods();
		foreach($refMethods as $RefMethod) {
			$methodName = $RefMethod->getName();
			$methodClass = $RefMethod->getDeclaringClass()->getName();
			// check signature of preHandle & postHandle hooks
			if(in_array($methodName, ['preHandle','postHandle'])) {
				if(!$RefMethod->isProtected()) throw new Exception(101, [$methodClass,$methodName]);
			// check signature of handling methods (skip protected/private methods, they can't be handler!)
			} elseif($RefMethod->isPublic() && !in_array($methodName,['handle']) && substr($methodName,0,2)!='__') {
				$action = $methodName;
				$config[$action] = [];
				foreach($RefMethod->getParameters() as $i => $RefParam) {
					$config[$action]['params'][$i]['name'] = $RefParam->getName();
					$config[$action]['params'][$i]['class'] = ($RefParam->getType() && !$RefParam->getType()->isBuiltin()) ? (new ReflectionClass($RefParam->getType()->getName()))->getName() : null;
					$config[$action]['params'][$i]['type'] = ($RefParam->getType() && $RefParam->getType()->isBuiltin()) ? $RefParam->getType()->getName() : null;
					$config[$action]['params'][$i]['optional'] = $RefParam->isOptional();
					$config[$action]['params'][$i]['default'] = ($RefParam->isDefaultValueAvailable()) ? $RefParam->getDefaultValue() : null;
				}
			}
		}
		return $config;
	}
}
